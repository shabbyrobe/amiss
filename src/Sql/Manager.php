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
     * @param string Class name
     * @return \Amiss\Meta 
     */
    public function getMeta($class)
    {
        // Do not put any logic in here at all. this is just syntactic sugar.
        // If you override this, nothing will actually call it - this class
        // uses $this->mapper->getMeta() internally
        return $this->mapper->getMeta($class);
    }

    public function get($class, ...$args)
    {
        $mapper = $this->mapper;

        $query = $args && $args[0] instanceof Query\Select ? $args[0] : Query\Select::fromParamArgs($args);
        $meta = !$class instanceof Meta ? $this->mapper->getMeta($class) : $class;
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
            $mappedRow = $mapper->mapRowToProperties($row, $meta);
        }

        if (!$mappedRow) {
            return null;
        }

        auto_relations: {
            $rel = $query->with;
            if ($meta->autoRelations && $query->follow) {
                $rel = $rel ? array_merge($rel, $meta->autoRelations) : $meta->autoRelations;
            }
            if ($rel) {
                $relations = [];
                $relators = [];

                $relQuery = new Criteria;
                foreach ((array) $rel as $relationId) {
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

    public function getList($class, ...$args)
    {
        $query = $args && $args[0] instanceof Query\Select ? $args[0] : Query\Select::fromParamArgs($args);
        $mapper = $this->mapper;
        $meta = !$class instanceof Meta ? $this->mapper->getMeta($class) : $class;

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
            $mappedRow = $mapper->mapRowToProperties($row, $meta);
            $mappedRows[] = $mappedRow;
        }

        $objects = [];
        if (!$mappedRows) {
            return $objects;
        }

        $related = [];

        auto_relations: {
            $relations = [];
            $relators = [];
            $rel = $query->with;
            if ($meta->autoRelations && $query->follow) {
                $rel = $rel ? array_merge($rel, $meta->autoRelations) : $meta->autoRelations;
            }
            if ($rel) {
                $relQuery = new Criteria;
                foreach ((array) $rel as $relationId) {
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

    public function getById($class, $id, array $criteria=null)
    {
        $key = isset($criteria['key']) ? $criteria['key'] : null;
        unset($criteria['key']);

        $query = $this->createKeyCriteria($class, $id, $key);
        if ($criteria) {
            $query = array_merge($criteria, $query);
        }
        return $this->get($class, $query);
    }
    
    public function count($class, ...$args)
    {
        $query = $args && $args[0] instanceof Query\Select ? $args[0] : Query\Select::fromParamArgs($args);
        $meta = !$class instanceof Meta ? $this->mapper->getMeta($class) : $class;
        
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

    public function exists($class, $id, $key='primary')
    {
        $meta = !$class instanceof Meta ? $this->mapper->getMeta($class) : $class;
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
    public function assignRelated($source, $relationNames=null, Meta $meta=null)
    {
        if (!$source) { return; }

        $stack = [];
        $sourceIsArray = is_array($source) || $source instanceof \Traversable;
        if (!$sourceIsArray) {
            $source = array($source);
        }
        $meta = $meta ?: $this->mapper->getMeta($source[0]);

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
 
    private function populateObjectsWithRelated(array $source, array $result, $relation)
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
                $source[$idx]->{$relation['name']} = $item;
            } else {
                call_user_func(array($source[$idx], $relation['setter']), $item);
            }

            if ($relatedMeta) {
                if ($relation[0] == 'one') {
                    $item = [$item];
                }
                foreach ($item as $i) {
                    if (!isset($relatedRelation['setter'])) {
                        $i->{$relatedRelation['name']} = $source[$idx];
                    } else {
                        call_user_func(array($i, $relatedRelation['setter']), $i);
                    }
                }
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
    public function getRelated($source, $relationName, $query=null, Meta $meta=null)
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
     * Supports the following signatures:
     *   insertTable($meta, array $propertyValues);
     *   insertTable($meta, Query\Insert $query);
     * 
     * - $meta can be an instance of Amiss\Meta or a class name.
     * - Property keys must exist in the corresponding Meta.
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
        $query->values = $this->mapper->mapPropertiesToRow($query->values, $meta);
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
     * Supports the following signatures:
     *   insert($object)
     *   insert($object, string $table)
     *   insert($object, Meta $meta)
     * 
     * @return int|null
     */
    public function insert($object, $arg=null)
    {
        $meta = null;
        $query = new Query\Insert;

        if ($arg instanceof Meta) {
            $meta = $arg;
        } elseif (is_string($arg)) {
            $query->table = $arg;
        } elseif (is_array($arg)) {
            throw new \BadMethodCallException("Please use insertTable()");
        } elseif ($arg) {
            throw new \InvalidArgumentException("Invalid type ".gettype($arg));
        }

        if (!$meta) {
            $meta = $this->mapper->getMeta($object);
        }
        if (!$meta->canInsert) {
            throw new Exception("Class {$meta->class} prohibits insert");
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

            $field = $meta->getField($meta->primary[0]);
            $handler = $this->mapper->determineTypeHandler($field['type']['id']);
            
            if ($handler instanceof \Amiss\Type\Identity) {
                if (!$lastInsertId) {
                    throw new \UnexpectedValueException();
                }

                if (($count = count($meta->primary)) != 1) {
                    throw new Exception(
                        "Last insert ID $lastInsertId found for class {$meta->class}. ".
                        "Expected 1 primary field, but class defines {$count}"
                    );
                }

                $generated = $handler->handleDbGeneratedValue($lastInsertId);
                if ($generated) {
                    // skip using populateObject - we don't need the type handler stack because
                    // we already used one to handle the value
                    if (!isset($field['getter'])) {
                        $object->{$meta->primary[0]} = $generated;
                    }
                    elseif (isset($field['setter'])) {
                        if ($field['setter'] !== false) {
                            $object->{$field['setter']}($generated);
                        } else {
                            throw new Exception("Tried to assign ID to read-only setter on {$meta->class}");
                        }
                    }
                }
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
     * Supports the following signatures:
     *   updateTable($classNameOrMeta, array $values, $criteria...);
     *   updateTable($classNameOrMeta, Query\Update $query, $criteria...);
     * 
     * @return void
     */
    public function updateTable($meta, ...$args)
    {
        $meta = $meta instanceof Meta ? $meta : $this->mapper->getMeta($meta);
        if (!$meta->canUpdate) {
            throw new Exception("Class {$meta->class} prohibits update");
        }

        $query = Query\Update::fromParamArgs($args);
        if (!$query->set) {
            throw new \InvalidArgumentException();
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
     * Supports the following signatures:
     *   update($object)
     *   update($object, $tableOrMeta)
     * 
     * @return void
     */
    public function update($object, $arg=null)
    {
        $meta = null;

        $query = new Query\Update();
        if (!is_object($object)) {
            throw new \BadMethodCallException("Please use updateTable()");
        } 
        if ($arg instanceof Meta) {
            $meta = $arg;
        } elseif (is_string($arg)) {
            $query->table = $arg;
        } 
        elseif ($arg) {
            throw new \InvalidArgumentException("Invalid type ".gettype($arg));
        }
         
        if (!$meta) {
            $meta = $this->mapper->getMeta($object);
        }
        if (!$meta->canUpdate) {
            throw new Exception("Class {$meta->class} prohibits update");
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
     * Supports the following signatures:
     *   deleteTable($classNameOrMeta, $criteria...);
     * 
     * @return void
     */
    public function deleteTable($meta, ...$args)
    {
        if (!isset($args[0])) {
            throw new \InvalidArgumentException("Cannot delete from table without a condition (pass the string '1=1' if you really meant to do this)");
        }
        $meta = $meta instanceof Meta ? $meta : $this->mapper->getMeta($meta);
        if (!$meta->canDelete) {
            throw new Exception("Class {$meta->class} prohibits update");
        }
        $query = Query\Criteria::fromParamArgs($args);
        return $this->executeDelete($meta, $query); 
    }
    
    /**
     * Delete an object from the database
     * 
     * Supports the following signatures:
     *   delete($object)
     *   delete($object, string $table)
     *   delete($object, Amiss\Meta $meta)
     * 
     * @return void
     */
    public function delete($object, $arg=null)
    {
        $meta = null;
        $query = new Query\Criteria();

        if (!is_object($object)) {
            throw new \BadMethodCallException("Please use deleteTable()");
        } 
        if ($arg instanceof Meta) {
            $meta = $arg;
        } elseif (is_string($arg)) {
            $query->table = $arg;
        } 
        elseif ($arg) {
            throw new \InvalidArgumentException("Invalid type ".gettype($arg));
        }

        if (!$meta) {
            $meta = $this->mapper->getMeta($object);
        }
        if (!$meta->canDelete) {
            throw new Exception("Class {$meta->class} prohibits delete");
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
     * This is a hack to allow active record to intercept saving and fire events.
     * You should not call it yourself as it will be removed as soon as I work out
     * a good way to remove it.
     * @return boolean
     */
    public function shouldInsert($object, Meta $meta=null)
    {
        $meta = $meta ?: $this->mapper->getMeta(get_class($object));
        if (!$meta->primary) {
            throw new Exception("No primary key for {$meta->class}");
        }

        $autoIncCnt = 0;
        $autoIncField = null;
        foreach ($meta->primary as $fieldName) {
            $field = $meta->getField($fieldName);

            // FIXME: this is horrible. what if you have a completely custom mapper
            // and the autoinc has a different name?
            if ($field['type']['id'] == 'autoinc') {
                ++$autoIncCnt;
                $autoIncField = $fieldName;
            }
        }
        if ($autoIncCnt != 1) {
            throw new Exception("Primary must have one and only one autoinc column");
        }
        $prival = $meta->getValue($object, $autoIncField);
        return $prival == false;
    }
    
    /**
     * If an object has an autoincrement primary key, insert or update as necessary.
     * 
     * @return void
     */
    public function save($object, Meta $meta=null)
    {
        $meta = $meta ?: $this->mapper->getMeta(get_class($object));

        $shouldInsert = $this->shouldInsert($object, $meta);
        
        if ($shouldInsert) {
            $this->insert($object, $meta);
        } else {
            $this->update($object, $meta);
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
            throw new Exception("Index $indexId does not exist on class {$meta->class}");
        }
        $index = $meta->indexes[$indexId];
        if (!$index['key']) {
            throw new Exception("Index $indexId is not a key index for class {$meta->class}");
        }
        if (!is_array($id)) {
            $id = array($id);
        }
        $where = array();
        
        foreach ($index['fields'] as $idx=>$p) {
            $idVal = isset($id[$p]) ? $id[$p] : (isset($id[$idx]) ? $id[$idx] : null);
            if (!$idVal) {
                throw new \InvalidArgumentException("Couldn't get ID value when getting {$meta->class} by id");
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

        if ($meta) {
            $meta = !$meta instanceof Meta ? $this->mapper->getMeta($meta) : $meta;
        }
        if (!$list) {
            return [];
        }
        $first = current($list);
        if (!$meta) {
            $meta = $this->mapper->getMeta($first);
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
        $first = current($list);
        if (!$meta) {
            $meta = $this->mapper->getMeta($first);
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
