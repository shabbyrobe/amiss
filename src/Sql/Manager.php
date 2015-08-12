<?php
namespace Amiss\Sql;

use Amiss\Exception;
use Amiss\Mapper;
use Amiss\Meta;
use Amiss\Sql\Query;
use Amiss\Sql\Query\Criteria;
use PDOK\Connector;

class Manager
{
    static $eventNames = [
        'beforeUpdate' , 'afterUpdate' ,
        'beforeInsert' , 'afterInsert' ,
        'beforeDelete' , 'afterDelete' ,
        'beforeSave'   , 'afterSave'   ,
    ];

    /**
     * @var PDOK\Connector
     */
    public $connector;
    
    /**
     * @var Amiss\Mapper
     */
    public $mapper;
    
    /**
     * @var (Amiss\Sql\Relator|callable)[]
     */
    public $relators = [];

    public $on = [];

    /**
     * @param PDOK\Connector|array  Database connector
     * @param Amiss\Mapper
     */
    public function __construct($connector, $mapper)
    {
        if (!$connector instanceof Connector) {
            $connector = Connector::create($connector);
        }
        $this->connector = $connector;
        $this->mapper = $mapper;
    }

    /**
     * @return PDOK\Connector
     */
    public function getConnector()
    {
        return $this->connector;
    }

    /**
     * @return \Amiss\Meta 
     */
    public function getMeta($id, $strict=true)
    {
        // Do not put any logic in here at all. this is just syntactic sugar.
        // If you override this, nothing will actually call it - this class
        // uses $this->mapper->getMeta() internally
        return $this->mapper->getMeta($id, $strict);
    }

    public function get($meta, ...$args)
    {
        $mapper = $this->mapper;

        $query = $args && $args[0] instanceof Query\Select ? $args[0] : Query\Select::fromParamArgs($args);
        $meta = !$meta instanceof Meta ? $this->mapper->getMeta($meta) : $meta;
        $object = null;

        // Hack to stop circular references in auto relations
        if (isset($query->stack[$meta->class])) {
            return;
        }

        list ($limit, $offset) = $query->getLimitOffset();
        if ($limit && $limit != 1) {
            throw new Exception("Limit must be one or zero");
        }
        
        list ($sql, $params, $props) = $query->buildQuery($meta);
        if ($props) {
            $params = $this->mapper->formatParams($meta, $props, $params);
        }
        
        $stmt = $this->getConnector()->prepare($sql)->execute($params);

        $mappedRow = null;
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if ($mappedRow) {
                throw new Exception("Query returned more than one row");
            }
            $mappedRow = $mapper->mapRowToProperties($meta, $row);
        }

        if (!$mappedRow) {
            return null;
        }

        auto_relations: {
            $rel = (array) $query->with;
            if ($meta->autoRelations && $query->follow) {
                $rel = $rel ? array_merge($rel, $meta->autoRelations) : $meta->autoRelations;
            }
            if ($rel) {
                $relations = [];
                $relators = [];

                $relQuery = new Criteria;
                foreach ((array) $rel as $relationId) {
                    if (!isset($meta->relations[$relationId])) {
                        throw new \UnexpectedValueException("Unknown relation $relationId on {$meta->class}");
                    }
                    $relation = $meta->relations[$relationId];
                    $relator = isset($relators[$relation[0]]) 
                        ? $relators[$relation[0]] 
                        : ($relators[$relation[0]] = $this->getRelator($relation));

                    $relQuery->stack = $query->stack;
                    $relQuery->stack[$meta->class] = true;
                    $mappedRow->{$relationId} = $relator->getRelated($meta, $mappedRow, $relation);
                }
            }
        }

        create: {
            $object = $mapper->createObject($meta, $mappedRow, $query->args);
            $mapper->populateObject($object, $mappedRow, $meta);
        }

        return $object;
    }

    public function getList($meta, ...$args)
    {
        $query = $args && $args[0] instanceof Query\Select ? $args[0] : Query\Select::fromParamArgs($args);
        $mapper = $this->mapper;
        $meta = !$meta instanceof Meta ? $this->mapper->getMeta($meta) : $meta;

        // Hack to stop circular references in auto relations
        if (isset($query->stack[$meta->class])) {
            return;
        }

        list ($sql, $params, $props) = $query->buildQuery($meta);
        if ($props) {
            $params = $this->mapper->formatParams($meta, $props, $params);
        }

        $stmt = $this->getConnector()->prepare($sql)->execute($params);

        $mappedRows = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $mappedRow = $mapper->mapRowToProperties($meta, $row);
            $mappedRows[] = $mappedRow;
        }

        $objects = [];
        if (!$mappedRows) {
            return $objects;
        }

        $related = [];

        auto_relations: {
            $rel = (array) $query->with;
            if ($meta->autoRelations && $query->follow) {
                $rel = $rel ? array_merge($rel, $meta->autoRelations) : $meta->autoRelations;
            }
            if ($rel) {
                $relations = [];
                $relators = [];

                $relQuery = new Criteria;
                foreach ((array) $rel as $relationId) {
                    if (!isset($meta->relations[$relationId])) {
                        throw new \UnexpectedValueException("Unknown relation $relationId on {$meta->class}");
                    }
                    $relation = $meta->relations[$relationId];
                    $relator = isset($relators[$relation[0]]) 
                        ? $relators[$relation[0]] 
                        : ($relators[$relation[0]] = $this->getRelator($relation));

                    $relQuery->stack = $query->stack;
                    $relQuery->stack[$meta->class] = true;
                    $cur = $relator->getRelatedForList($meta, $mappedRows, $relation, $relQuery);
                    if ($cur) {
                        $related[$relationId] = $cur;
                    }
                }
            }
        }

        foreach ($mappedRows as $idx=>$mappedRow) {
            foreach ($related as $relId=>$objs) {
                $mappedRow->{$relId} = isset($objs[$idx]) ? $objs[$idx] : null;
            }
            $object = $mapper->createObject($meta, $mappedRow, $query->args);
            $mapper->populateObject($object, $mappedRow, $meta);
            $objects[] = $object;
        }
        return $objects;
    }

    /**
     * Dunno about this. Consider it unstable, don't count on it still
     * being here in v6
     */
    public function selectList($meta, ...$args)
    {
        if (!$args) {
            throw new \InvalidArgumentException();
        }
        
        $fields = null;
        $query = null;
        if (!isset($args[1]) && $args[0] instanceof Query\Select) {
            $query = $args[0];
        }
        elseif ((is_array($args[0]) || is_string($args[0])) && !isset($args[2])) {
            $fields = $args[0];
            $query = isset($args[1]) ? $args[1] : null;
        }
        else {
            throw new \InvalidArgumentException();
        }

        $query = $query instanceof Query\Select ? $query : Query\Select::fromParamArgs([$query]);
        if ($fields) {
            $query->fields = $fields;
        }
        if (!$query->fields) {
            throw new \InvalidArgumentException();
        }
        $singleMode = false;
        if (is_string($query->fields)) {
            $singleMode = true;
            $query->fields = [$query->fields];
        }

        $mapper = $this->mapper;
        $meta = !$meta instanceof Meta ? $this->mapper->getMeta($meta) : $meta;

        list ($sql, $params, $props) = $query->buildQuery($meta);
        if ($props) {
            $params = $this->mapper->formatParams($meta, $props, $params);
        }

        $stmt = $this->getConnector()->prepare($sql)->execute($params);

        $mappedRows = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $mappedRow = $mapper->mapRowToProperties($meta, $row);
            if ($singleMode) {
                $mappedRows[] = current($mappedRow);
            } else {
                $mappedRows[] = $mappedRow;
            }
        }

        return $mappedRows;
    }

    public function getById($meta, $id, array $criteria=null)
    {
        $key = isset($criteria['key']) ? $criteria['key'] : null;
        unset($criteria['key']);

        $query = $this->createKeyCriteria($meta, $id, $key);
        if ($criteria) {
            $query = array_merge($criteria, $query);
        }
        return $this->get($meta, $query);
    }

    /**
     * count ( $meta )
     * count ( $meta , string $where [ , array $params ] )
     * count ( $meta , array $query )
     * count ( $meta , \Amiss\Sql\Query\Select $query )
     *
     * @param  $meta string|Amiss\Meta
     * @return int
     */
    public function count($meta, ...$args)
    {
        $query = $args && $args[0] instanceof Query\Select ? $args[0] : Query\Select::fromParamArgs($args);
        $meta = !$meta instanceof Meta ? $this->mapper->getMeta($meta) : $meta;
        
        $table = $query->table ?: $meta->table;
        
        list ($where, $params, $props) = $query->buildClause($meta);
        if ($props) {
            $params = $this->mapper->formatParams($meta, $props, $params);
        }

        $t = ($meta->schema ? "`{$meta->schema}`." : null)."`{$table}`";
        $query = "SELECT COUNT(1) FROM $t "
            .($where  ? "WHERE $where" : '');

        $stmt = $this->getConnector()->prepare($query)->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function exists($meta, $id, $key='primary')
    {
        $meta = !$meta instanceof Meta ? $this->mapper->getMeta($meta) : $meta;
        $query = new \Amiss\Sql\Query\Select;
        $query->setParams([$this->createKeyCriteria($meta, $id, $key)]);

        return $this->count($meta, $query) > 0;
    }

    /**
     * Retrieve related objects from the database and assign them to the source
     * if the source field is not yet populated.
     * 
     * @param object|array Source objects to assign relations for
     * @param string|array The name of the relation(s) to assign
     * @return void
     */
    public function assignRelated($source, $relationNames=null, $meta=null)
    {
        if (!$source) { return; }

        $stack = [];
        $sourceIsArray = is_array($source) || $source instanceof \Traversable;
        if (!$sourceIsArray) {
            $source = array($source);
        }

        if ($meta !== null) {
            $meta = !$meta instanceof Meta ? $this->mapper->getMeta($meta) : $meta;
        } else {
            $meta = $this->mapper->getMeta(get_class($source[0]));
        }

        if (!$relationNames) {
            if ($meta->autoRelations) {
                $relationNames = $meta->autoRelations;
            } else {
                throw new Exception("relationNames not passed, class {$meta->class} does not define autoRelations");
            }
        }

        $relationMap = [];
        foreach ((array)$relationNames as $relationName) {
            if (!isset($meta->relations[$relationName])) {
                throw new Exception("Unknown relation $relationName on {$meta->class}");
            }
            $relationMap[$relationName] = $rel = $meta->relations[$relationName];
            if ($rel['mode'] == 'class') {
                throw new Exception("Relation $relationName is not assignable for class {$meta->class}");
            }
        }

        $done = [];
        foreach ($relationMap as $relationName=>$relation) {
            if (isset($done[$relationName])) {
                continue;
            }
            $done[$relationName] = true;

            $missing = [];
            foreach ($source as $idx=>$item) {
                // we have to assume it's missing if we see a getter as there
                // may be serious unintended side effects from calling a getter
                // that may be unpopulated. it might lazy load if it's an active
                // record, or it might throw an exception because the 'set' hasn't
                // been called.

                // check for isset as well as falsey for the situation where we're using
                // stdClasses as the response object
                if (isset($relation['getter']) || !isset($item->{$relationName}) || !$item->{$relationName}) {
                    $missing[$idx] = $item;
                }
            }

            if ($missing) {
                $result = $this->getRelated($missing, $relationName, ['stack'=>$stack], $meta);
                $relator = $this->getRelator($meta, $relationName);
                $this->populateObjectsWithRelated($missing, $result, $relation);
            }
        }
    }
 
    private function populateObjectsWithRelated(array $source, array $result, $relation, $default=null)
    {
        $relatedMeta = null;

        // if the relation is a many relation and the inverse property is
        // specified, we want to populate the 'one' side of the relation
        // with the source
        if (isset($relation['inverse'])) {
            $relatedMeta = $this->mapper->getMeta($relation['of']);
            $relatedRelation = $relatedMeta->relations[$relation['inverse']];
        }

        foreach ($result as $idx=>$item) {
            // no read only support... why would you be assigning relations to
            // a read only object?
            if (!isset($relation['setter'])) {
                $source[$idx]->{$relation['id']} = $item;
            } else {
                call_user_func(array($source[$idx], $relation['setter']), $item);
            }

            if ($relatedMeta) {
                // FIXME: leaky abstraction
                if ($relation[0] == 'one') {
                    $item = [$item];
                }
                foreach ($item as $i) {
                    if (!isset($relatedRelation['setter'])) {
                        $i->{$relatedRelation['id']} = $source[$idx];
                    } else {
                        call_user_func(array($i, $relatedRelation['setter']), $i);
                    }
                }
            }

            unset($source[$idx]);
        }

        foreach ($source as $item) {
            if (!isset($relation['setter'])) {
                $item->{$relation['id']} = $default;
            } else {
                call_user_func(array($item, $relation['setter']), $default);
            }
        }
    }

    public function getRelator($arg1, $arg2=null)
    {
        if ($arg2) {
            list ($meta, $relationName) = [$arg1, $arg2];
            if (!isset($meta->relations[$relationName])) {
                throw new Exception("Relation $relationName not found on class {$meta->class}");
            }
            $relation = $meta->relations[$relationName];
        } else {
            $relation = $arg1;
        }

        if (!isset($this->relators[$relation[0]])) {
            throw new Exception("Relator {$relation[0]} not found");
        }

        $relator = $this->relators[$relation[0]];
        if (!$relator instanceof Relator) {
            $relator = $this->relators[$relation[0]] = call_user_func($relator, $this);
        }

        return $relator;
    }   

    /**
     * Get related objects from the database
     * 
     * Supports the following signatures:
     * 
     * - getRelated( object|array $source , string $relationName )
     * - getRelated( object|array $source , string $relationName , $query )
     * - getRelated( object|array $source , string $relationName , Meta $meta )
     * - getRelated( object|array $source , string $relationName , $query, Meta $meta )
     *
     * @return object[]
     */
    public function getRelated($source, $relationName, $query=null, $meta=null)
    {
        if (!$source) { return; }

        $sourceIsArray = false;

        $test = $source;
        if (is_array($test) || $test instanceof \Traversable) {
            $sourceIsArray = true;
            $test = $test[0];
        }

        if (!$meta) {
            if ($query instanceof Meta) {
                list ($meta, $query) = [$query, null];
            } else {
                $meta = $this->mapper->getMeta(get_class($test));
            }
        }
        else {
            $meta = $meta instanceof Meta ? $meta : $this->mapper->getMeta($meta);
        }

        if (!isset($meta->relations[$relationName])) {
            throw new Exception("Unknown relation $relationName on {$meta->class}");
        }
        
        $relation = $meta->relations[$relationName];
        $relator = $this->getRelator($relation);
        
        if ($query) {
            // need to support both Query\Criteria and Query\Select
            // this is a cheeky hack - the API doesn't declare support for
            // Select in Relators because it carries promises of things like 
            // 'fields' and whatnot that we'll never be able to satisfy. 
            // That whole hierarchy needs to be cleaned
            // up into a bunch of traits so we can have RelatorCriteria or something.
            $query = $query instanceof Query\Criteria ? $query : Query\Select::fromParamArgs([$query]);
            $stack = $query->stack;
        }
        else {
            $stack = [];
        }

        $stack[$meta->class] = true;
        if ($sourceIsArray) {
            return $relator->getRelatedForList($meta, $source, $relation, $query ?: null, $stack);
        } else {
            return $relator->getRelated($meta, $source, $relation, $query ?: null, $stack);
        }
    }
    
    /**
     * Insert values into a table
     * 
     *   insertTable ( $meta , array $propertyValues );
     *   insertTable ( $meta , Query\Insert $query );
     * 
     * - $meta can be an instance of Amiss\Meta or a class name.
     * - $propertyValues keys must exist in the corresponding Meta.
     * 
     * @return int|null
     */
    public function insertTable($meta, $query)
    {
        $meta = $meta instanceof Meta ? $meta : $this->mapper->getMeta($meta);
        if (!$meta->canInsert) {
            throw new Exception("Class {$meta->class} prohibits insert");
        }

        $query = $query instanceof Query\Insert ? $query : new Query\Insert(['values'=>$query]);
        $query->values = $this->mapper->mapPropertiesToRow($meta, $query->values);
        if (!$query->table) {
            $query->table = $meta->table;
        }

        list ($sql, $params) = $query->buildQuery($meta);
        // no need to formatParams here - they're already field names

        $stmt = $this->getConnector()->prepare($sql);
        $stmt->execute($params);
        $lastInsertId = $this->getConnector()->lastInsertId();
        return $lastInsertId;
    }

    /**
     * Insert an object into the database, or values into a table
     * 
     *   insert($object)
     *   insert($object, Meta $meta)
     * 
     * @return int|null
     */
    public function insert($object, $meta=null)
    {
        $query = new Query\Insert;

        if (is_array($meta)) {
            throw new \BadMethodCallException("Please use insertTable()");
        }

        if ($meta) {
            $meta = !$meta instanceof Meta ? $this->mapper->getMeta($meta) : $meta;
        } else {
            $meta = $this->mapper->getMeta(get_class($object));
        }

        if (!$meta->canInsert) {
            throw new Exception("Meta {$meta->id} prohibits insert");
        }

        event_before: {
            if (isset($meta->on['beforeInsert'])) {
                foreach ($meta->on['beforeInsert'] as $cb) { $cb = [$object, $cb]; $cb(); }
            }
            if (isset($this->on['beforeInsert'])) {
                foreach ($this->on['beforeInsert'] as $cb) { $cb($object, $meta); }
            }
        }

        query: {
            $query->values = $this->mapper->mapObjectToRow($object, $meta, 'insert');
            if (!$query->table) {
                $query->table = $meta->table;
            }

            list ($sql, $params, $props) = $query->buildQuery($meta);
            $stmt = $this->getConnector()->prepare($sql);
            $stmt->execute($params);
        }
        
        $lastInsertId = null;

        // we need to be careful with "lastInsertId": SQLite generates one even without a PRIMARY
        if ($object && $meta->primary) {
            $lastInsertId = $this->getConnector()->lastInsertId();
            if ($lastInsertId && $meta->autoinc) {
                $field = $meta->fields[$meta->autoinc];
                $handler = $this->mapper->determineTypeHandler(Mapper::AUTOINC_TYPE);
                if (!$handler) {
                    throw new \UnexpectedValueException();
                }
                $meta->setValue($object, $meta->autoinc, $lastInsertId);
            }
        }
        
        event_after: {
            if (isset($meta->on['afterInsert'])) {
                foreach ($meta->on['afterInsert'] as $cb) { $cb = [$object, $cb]; $cb(); }
            }
            if (isset($this->on['afterInsert'])) {
                foreach ($this->on['afterInsert'] as $cb) { $cb($object, $meta); }
            }
        }

        return $lastInsertId;
    }

    /**
     * Update a table by criteria.
     * 
     *   updateTable ( $meta , array $values , $criteria... )
     *   updateTable ( $meta , Query\Update $query , $criteria... )
     * 
     * @return void
     */
    public function updateTable($meta, ...$args)
    {
        $meta = !$meta instanceof Meta ? $this->mapper->getMeta($meta) : $meta;
        if (!$meta->canUpdate) {
            throw new Exception("Meta {$meta->id} prohibits update");
        }

        $query = Query\Update::fromParamArgs($args);
        if (!$query->set) {
            throw new \InvalidArgumentException("Query missing 'set' values");
        }

        list ($sql, $params, $setProps, $whereProps) = $query->buildQuery($meta);
        if ($setProps) {
            $params = $this->mapper->formatParams($meta, $setProps, $params);
        }
        if ($whereProps) {
            $params = $this->mapper->formatParams($meta, $whereProps, $params);
        }

        return $this->getConnector()->exec($sql, $params);
    }
    
    /**
     * Update an object in the database, or update a table by criteria.
     * 
     *   update ( $object )
     *   update ( $object , $meta )
     * 
     * @return void
     */
    public function update($object, $meta=null)
    {
        $query = new Query\Update();
        if (!is_object($object)) {
            throw new \BadMethodCallException("Please use updateTable()");
        } 

        if ($meta) {
            $meta = !$meta instanceof Meta ? $this->mapper->getMeta($meta) : $meta;
        } else {
            $meta = $this->mapper->getMeta(get_class($object));
        }

        if (!$meta->primary) {
            throw new Exception("Cannot update: meta {$meta->id} has no primary key");
        }
        if (!$meta->canUpdate) {
            throw new Exception("Meta {$meta->id} prohibits update");
        }

        event_before: {
            if (isset($meta->on['beforeUpdate'])) {
                foreach ($meta->on['beforeUpdate'] as $cb) { $cb = [$object, $cb]; $cb(); }
            }
            if (isset($this->on['beforeUpdate'])) {
                foreach ($this->on['beforeUpdate'] as $cb) { $cb($object, $meta); }
            }
        }

        query: {
            $query->set = $this->mapper->mapObjectToRow($object, $meta, 'update');
            $query->where = $meta->getIndexValue($object);

            list ($sql, $params, $props) = $query->buildQuery($meta);
            // don't need to do formatParams - it's already covered by the mapPropertiesToRow call in
            // table update mode

            $return = $this->getConnector()->exec($sql, $params);
        }

        event_after: {
            if (isset($meta->on['afterUpdate'])) {
                foreach ($meta->on['afterUpdate'] as $cb) { $cb = [$object, $cb]; $cb(); }
            }
            if (isset($this->on['afterUpdate'])) {
                foreach ($this->on['afterUpdate'] as $cb) { $cb($object, $meta); }
            }
        }
        return $return;
    }
    
    /**
     * Delete from a table by criteria
     * 
     *   deleteTable ( $meta , $criteria... );
     * 
     * @return void
     */
    public function deleteTable($meta, ...$args)
    {
        if (!isset($args[0])) {
            throw new \InvalidArgumentException("Cannot delete from table without a condition (pass the string '1=1' if you really meant to do this)");
        }
        $meta = !$meta instanceof Meta ? $this->mapper->getMeta($meta) : $meta;
        if (!$meta->canDelete) {
            throw new Exception("Meta {$meta->id} prohibits update");
        }
        $query = Query\Criteria::fromParamArgs($args);
        return $this->executeDelete($meta, $query); 
    }
    
    /**
     * Delete an object from the database
     *
     *   delete ( $object )
     *   delete ( $object, $meta )
     * 
     * @return void
     */
    public function delete($object, $meta=null)
    {
        $query = new Query\Criteria();

        if (!is_object($object)) {
            throw new \BadMethodCallException("Please use deleteTable()");
        } 

        if ($meta) {
            $meta = !$meta instanceof Meta ? $this->mapper->getMeta($meta) : $meta;
        } else {
            $meta = $this->mapper->getMeta(get_class($object));
        }

        if (!$meta->canDelete) {
            throw new Exception("Meta {$meta->id} prohibits delete");
        }

        event_before: {
            if (isset($meta->on['beforeDelete'])) {
                foreach ($meta->on['beforeDelete'] as $cb) { $cb = [$object, $cb]; $cb(); }
            }
            if (isset($this->on['beforeDelete'])) {
                foreach ($this->on['beforeDelete'] as $cb) { $cb($object, $meta); }
            }
        }

        $query->where = $meta->getIndexValue($object);
        $return = $this->executeDelete($meta, $query);

        event_after: {
            if (isset($meta->on['afterDelete'])) {
                foreach ($meta->on['afterDelete'] as $cb) { $cb = [$object, $cb]; $cb(); }
            }
            if (isset($this->on['afterDelete'])) {
                foreach ($this->on['afterDelete'] as $cb) { $cb($object, $meta); }
            }
        }

        return $return;
    }
    
    /** 
     * @param mixed $meta Class name or Amiss\Meta
     */
    public function deleteById($meta, $id, $key='primary')
    {
        return $this->deleteTable($meta, $this->createKeyCriteria($meta, $id, $key));
    }

    /**
     * If an object has an autoincrement primary key, insert or update as necessary.
     * 
     * @return void
     */
    public function save($object, $meta=null)
    {
        if ($meta) {
            $meta = !$meta instanceof Meta ? $this->mapper->getMeta($meta) : $meta;
        } else {
            $meta = $this->mapper->getMeta(get_class($object));
        }

        if (!$meta->primary) {
            throw new Exception("No primary key for {$meta->id}");
        }

        $shouldInsert = null;
        $prival = null;
        if ($meta->autoinc) {
            $prival = $meta->getValue($object, $meta->autoinc);
            $shouldInsert = !$prival;
        } else {
            $prival = $meta->getIndexValue($object);
        }

        event_before: {
            if (isset($meta->on['beforeSave'])) {
                foreach ($meta->on['beforeSave'] as $cb) { $cb = [$object, $cb]; $cb(); }
            }
            if (isset($this->on['beforeSave'])) {
                foreach ($this->on['beforeSave'] as $cb) { $cb($object, $meta); }
            }
        }

        if ($shouldInsert === null) {
            $newpri = $meta->getIndexValue($object);
            $shouldInsert = $newpri != $prival;
        }

        if ($shouldInsert) {
            $this->insert($object, $meta);
        } else {
            $this->update($object, $meta);
        }

        event_after: {
            if (isset($meta->on['afterSave'])) {
                foreach ($meta->on['afterSave'] as $cb) { $cb = [$object, $cb]; $cb(); }
            }
            if (isset($this->on['afterSave'])) {
                foreach ($this->on['afterSave'] as $cb) { $cb($object, $meta); }
            }
        }
    }

    /**
     * Creates an array criteria for a key index
     * @param mixed $meta Meta or class name
     * @return array Criteria
     */
    public function createKeyCriteria($meta, $id, $indexId=null)
    {
        if ($indexId == null) {
            $indexId = 'primary';
        }
        $meta = !$meta instanceof Meta ? $this->mapper->getMeta($meta) : $meta;
        if (!isset($meta->indexes[$indexId])) {
            throw new Exception("Index $indexId does not exist on meta {$meta->id}");
        }
        $index = $meta->indexes[$indexId];
        if (!$index['key']) {
            throw new Exception("Index $indexId is not a key index for meta {$meta->id}");
        }
        if (!is_array($id)) {
            $id = array($id);
        }
        $where = array();
        
        foreach ($index['fields'] as $idx=>$p) {
            $idVal = isset($id[$p]) ? $id[$p] : (isset($id[$idx]) ? $id[$idx] : null);
            if (!$idVal) {
                throw new \InvalidArgumentException("Couldn't get ID value when getting {$meta->id} by index '{$indexId}'");
            }
            $where[$p] = $idVal;
        }
        
        return array('where'=>$where);
    }

    protected function executeDelete(Meta $meta, Query\Criteria $criteria)
    {
        $table = $criteria->table ?: $meta->table;

        list ($whereClause, $whereParams, $whereProps) = $criteria->buildClause($meta);
        if (!$whereClause) {
            throw new \UnexpectedValueException("Empty where clause");
        }
        if ($whereProps) {
            $whereParams = $this->mapper->formatParams($meta, $whereProps, $whereParams);
        }

        $t = ($meta->schema ? "`{$meta->schema}`." : null)."`{$table}`";
        $sql = "DELETE FROM $t WHERE $whereClause";
        $stmt = $this->getConnector()->prepare($sql)->execute($whereParams);
    }

    /**
     * Iterate over an array of objects and returns an array of objects
     * indexed by a property
     *
     * Doesn't really belong in Manager from a purist POV, but it's very convenient for
     * users for it to be here.
     * 
     * @return array
     */
    public function indexBy($list, $property, $meta=null, $allowDupes=null, $ignoreNulls=null)
    {
        $allowDupes  = $allowDupes  !== null ? $allowDupes  : false;
        $ignoreNulls = $ignoreNulls !== null ? $ignoreNulls : true;

        if (!$list) {
            return [];
        }
        if ($meta) {
            $meta = !$meta instanceof Meta ? $this->mapper->getMeta($meta) : $meta;
        } else {
            if (!($first = current($list))) {
                throw new \UnexpectedValueException();
            }
            $meta = $this->mapper->getMeta(get_class($first));
        }

        $index = array();

        $props = $meta ? $meta->getProperties() : [];
        foreach ($list as $object) {
            $propDef = !isset($props[$property]) ? null : $props[$property];
            $value = !$propDef || !isset($propDef['getter']) 
                ? $object->{$property} 
                : call_user_func(array($object, $propDef['getter']));

            if ($value === null && $ignoreNulls) {
                continue;
            }
            if (!$allowDupes && isset($index[$value])) {
                throw new \UnexpectedValueException("Duplicate value for property $property");
            }
            $index[$value] = $object;
        }
        return $index;
    }

    /**
     * Iterate over an array of objects and group them by the value of a property
     *
     * Doesn't really belong in Manager from a purist POV, but it's very convenient for
     * users for it to be here.
     * 
     * @return array[group] = class[]
     */
    public function groupBy($list, $property, $meta=null)
    {
        if (!$list) {
            return [];
        }
        if (!$meta) {
            if (!($first = current($list))) {
                throw new \UnexpectedValueException();
            }
            $meta = $this->mapper->getMeta(get_class($first));
        }

        $groups = [];

        $props = $meta->getProperties();
        foreach ($list as $object) {
            $propDef = !isset($props[$property]) ? null : $props[$property];
            $value = !$propDef || !isset($propDef['getter']) 
                ? $object->{$property} 
                : call_user_func(array($object, $propDef['getter']));

            $groups[$value][] = $object;
        }
        return $groups;
    }


    /**
     * @ignore
     */
    public function __get($name)
    {
        throw new \BadMethodCallException("$name does not exist");
    }
    
    /**
     * @ignore
     */
    public function __set($name, $value)
    {
        throw new \BadMethodCallException("$name does not exist");
    }
    
    /**
     * @ignore
     */
    public function __isset($name)
    {
        throw new \BadMethodCallException("$name does not exist");
    }
    
    /**
     * @ignore
     */
    public function __unset($name)
    {
        throw new \BadMethodCallException("$name does not exist");
    }
}
