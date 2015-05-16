<?php
namespace Amiss\Sql;

use Amiss\Exception;
use Amiss\Mapper;
use Amiss\Meta;
use Amiss\Sql\Query;
use Amiss\Sql\Query\Criteria;
use PDOK\Connector;

/**
 * Amiss query manager. This is the core of Amiss' functionality.
 * 
 * @package Manager
 */
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
    
    /**
     * @param PDOK\Connector|array  Database connector
     * @param Amiss\Mapper
     */
    public function __construct($connector, $mapper)
    {
        if (is_array($connector)) {
            $connector = Connector::create($connector);
        }
        if (!$connector instanceof Connector) {
            throw new \InvalidArgumentException();
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
            $mappedRow = $mapper->toProperties($row, $meta);
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
            $mappedRow = $mapper->toProperties($row, $meta);
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

    public function getByKey($class, $key, $id, $args=null)
    {
        $query = $this->createKeyCriteria($class, $id, $key);
        if ($args) {
            $query['args'] = $args; 
        }
        return $this->get($class, $query);
    }

    public function getById($class, $id, $args=null)
    {
        $query = $this->createKeyCriteria($class, $id);
        if ($args) {
            $query['args'] = $args; 
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
        
        $field = '*';
        if ($meta->primary && count($meta->primary) == 1) {
            $metaField = $meta->getField($meta->primary[0]);
            $field = $metaField['name'];
        }
        
        $query = "SELECT COUNT($field) FROM $table "
            .($where  ? "WHERE $where" : '');

        $stmt = $this->getConnector()->prepare($query)->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function exists($class, $id)
    {
        $meta = !$class instanceof Meta ? $this->mapper->getMeta($class) : $class;
        $query = new \Amiss\Sql\Query\Criteria;
        $query->setParams([$this->createKeyCriteria($meta, $id)]);
        if (!$meta->primary) {
            throw new \InvalidArgumentException();
        }

        list ($where, $params, $props) = $query->buildClause($meta);
        if ($props) {
            $params = $this->mapper->formatParams($meta, $props, $params);
        }

        $fields = implode(', ', array_values($meta->primary));
        $query = "SELECT COUNT($fields) FROM {$meta->table} "
            .($where  ? "WHERE $where" : '');

        $stmt = $this->getConnector()->prepare($query)->execute($params);
        return ((int)$stmt->fetchColumn()) > 0;
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
            return $relator->getRelatedForList($meta, $source, $relation, $query, $stack);
        } else {
            return $relator->getRelated($meta, $source, $relation, $query, $stack);
        }
    }
    
    /**
     * Insert values into a table
     * 
     * Supports the following signatures:
     *   insert($meta, array $propertyValues);
     *   insert($meta, Query\Insert $query);
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
        if (!$query->table) {
            $query->table = $meta->table;
        }

        list ($sql, $params, $props) = $query->buildQuery($meta);
        if ($props) {
            $params = $this->mapper->formatParams($meta, $props, $params);
        }

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

        $query->values = $this->mapper->fromObject($object, $meta, 'insert');
        if (!$query->table) {
            $query->table = $meta->table;
        }

        list ($sql, $params, $props) = $query->buildQuery($meta);

        $stmt = $this->getConnector()->prepare($sql);
        $stmt->execute($params);
        
        $lastInsertId = null;

        // we need to be careful with "lastInsertId": SQLite generates one even without a PRIMARY
        if ($object && $meta->primary) {
            $lastInsertId = $this->getConnector()->lastInsertId();

            if (!$lastInsertId) {
                throw new \UnexpectedValueException();
            }

            if (($count = count($meta->primary)) != 1) {
                throw new Exception(
                    "Last insert ID $lastInsertId found for class {$meta->class}. ".
                    "Expected 1 primary field, but class defines {$count}"
                );
            }

            $field = $meta->getField($meta->primary[0]);
            $handler = $this->mapper->determineTypeHandler($field['type']['id']);
            
            if ($handler instanceof \Amiss\Type\Identity) {
                $generated = $handler->handleDbGeneratedValue($lastInsertId);
                if ($generated) {
                    // skip using populateObject - we don't need the type handler stack because we 
                    // already used one to handle the value
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
        
        return $lastInsertId;
    }

    /**
     * Update a table by criteria.
     * 
     * Supports the following signatures:
     *   update($classNameOrMeta, array $values, $criteria...);
     *   update($classNameOrMeta, Query\Update $query, $criteria...);
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

        $query->set = $this->mapper->fromObject($object, $meta, 'update');
        $query->where = $meta->getPrimaryValue($object);
        
        list ($sql, $params, $props) = $query->buildQuery($meta);
        // don't need to do formatParams - it's already covered by the fromProperties call in
        // table update mode

        return $this->getConnector()->exec($sql, $params);
    }
    
    /**
     * Delete an object from the database, or delete objects from a table by criteria.
     * 
     * Supports the following signatures:
     *   delete($object)
     *   delete($object, $tableOrMeta)
     *   delete($classNameOrMeta, $criteria...);
     * 
     * @return void
     */
    public function delete($first, ...$args)
    {
        $meta = null;
        $criteria = null;
 
        if (is_object($first) && !$first instanceof Meta) {
            $object = $first;
            $criteria = new Query\Criteria();
            if (isset($args[0])) {
                if ($args[0] instanceof Meta) {
                    // Signature: delete($object, Meta $meta)
                    $meta = $args[0];
                }
                elseif (is_string($args[0])) {
                    // Signature: delete($object, $table)
                    $query->table = $args[0];
                }
            }
            if (!$meta) {
                $meta = $this->mapper->getMeta(get_class($object));
            }
            $criteria->where = $meta->getIndexValue($object);
        }
        else {
            if (!isset($args[0])) {
                throw new \InvalidArgumentException("Cannot delete from table without a condition");
            }
            $meta = !$first instanceof Meta ? $this->mapper->getMeta($first) : $first;
            $criteria = $args[0] instanceof Query\Criteria ? $args[0] : Query\Criteria::fromParamArgs($args);
        }

        return $this->executeDelete($meta, $criteria);
    }
    
    /** 
     * @param mixed $meta Class name or Amiss\Meta
     */
    public function deleteById($meta, $id)
    {
        return $this->delete($meta, $this->createKeyCriteria($meta, $id));
    }

    /**
     * This is a hack to allow active record to intercept saving and fire events.
     * @return boolean
     */
    public function shouldInsert($object, Meta $meta=null)
    {
        $meta = $meta ?: $this->mapper->getMeta(get_class($object));
        $nope = false;
        if (!$meta->primary || count($meta->primary) > 1) {
            $nope = true;
        }
        else {
            $field = $meta->getField($meta->primary[0]);
            if ($field['type']['id'] != 'autoinc') {
                $nope = true;
            }
        }
        if ($nope) {
            throw new Exception("Manager requires a single-column autoincrement primary if you want to call 'save'.");
        }
        
        $prival = $meta->getPrimaryValue($object);
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
    public function createKeyCriteria($meta, $id, $indexId='primary')
    {
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

    protected function executeDelete($meta, Query\Criteria $criteria)
    {
        if (!$meta instanceof Meta) {
            $meta = $this->mapper->getMeta($meta);
            if (!$meta instanceof Meta) {
                throw new \InvalidArgumentException();
            }
        }

        if (!$meta->canDelete) {
            throw new Exception("Class {$meta->class} prohibits delete");
        }

        $table = $criteria->table ?: $meta->table;

        list ($whereClause, $whereParams, $whereProps) = $criteria->buildClause($meta);
        if (!$whereClause) {
            throw new \UnexpectedValueException("Empty where clause");
        }
        if ($whereProps) {
            $whereParams = $this->mapper->formatParams($meta, $whereProps, $whereParams);
        }

        $sql = "DELETE FROM $table WHERE $whereClause";
        $stmt = $this->getConnector()->prepare($sql)->execute($whereParams);
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
