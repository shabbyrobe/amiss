<?php
namespace Amiss\Sql;

use Amiss\Exception;
use Amiss\Mapper;
use Amiss\Meta;
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

    public function get($class)
    {
        $mapper = $this->mapper;
        $query = $this->createQueryFromArgs(array_slice(func_get_args(), 1));
        $meta = $mapper->getMeta($class);
        $object = null;

        // Hack to stop circular references in auto relations
        if (isset($query->stack[$meta->class])) {
            return;
        }

        list ($limit, $offset) = $query->getLimitOffset();
        if ($limit && $limit != 1) {
            throw new Exception("Limit must be one or zero");
        }
        
        list ($sql, $params) = $query->buildQuery($meta);
        
        $stmt = $this->getConnector()->prepare($sql)->execute($params);

        $mappedRow = null;
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if ($mappedRow) {
                throw new Exception("Query returned more than one row");
            }
            $mappedRow = $mapper->toProperties($row, $meta);
        }

        if ($mappedRow) {
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
                    $mappedRow->{$relationId} = $relator->getRelated($meta, $mappedRow, $relation);
                }
            }
            $object = $mapper->createObject($meta, $mappedRow, $query->args);
            $mapper->populateObject($object, $mappedRow, $meta);
        }

        return $object;
    }

    public function getList($class)
    {
        $query = $this->createQueryFromArgs(array_slice(func_get_args(), 1));
        $mapper = $this->mapper;
        $meta = $mapper->getMeta($class);

        // Hack to stop circular references in auto relations
        if (isset($query->stack[$meta->class])) {
            return;
        }

        list ($sql, $params) = $query->buildQuery($meta);
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

        $relations = [];
        $relators = [];
        $related = [];

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

    public function getById($class, $id, $args=null)
    {
        $query = $this->createIdCriteria($class, $id);
        if ($args) {
            $query['args'] = $args; 
        }
        return $this->get($class, $query);
    }
    
    public function count($class, $query=null)
    {
        $query = $this->createQueryFromArgs(array_slice(func_get_args(), 1));
        $meta = $this->mapper->getMeta($class);
        
        $table = $query->table ?: $meta->table;
        
        list ($where, $params) = $query->buildClause($meta);
        
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
        $criteria = $this->createIdCriteria($class, $id);
        $query = new \Amiss\Sql\Query\Criteria;
        $this->populateWhereAndParamsFromArgs($query, [$criteria]);
        $meta = $this->mapper->getMeta($class);
        if (!$meta->primary) {
            throw new \InvalidArgumentException();
        }

        list ($where, $params) = $query->buildClause($meta);

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
    public function assignRelated($source, $relationNames=null, $stack=[])
    {
        if (!$source) { return; }

        $sourceIsArray = is_array($source) || $source instanceof \Traversable;
        if (!$sourceIsArray) {
            $source = array($source);
        }

        $meta = $this->mapper->getMeta($source[0]);
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
                if (isset($relation['getter']) || !$item->{$relationName}) {
                    $missing[$idx] = $item;
                }
            }

            if ($missing) {
                $result = $this->getRelated($missing, $relationName, ['stack'=>$stack]);
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
     * @param object|array Source objects to get relations for
     * @param string The name of the relation to assign
     * @param query Optional criteria to limit the result
     * @return object[]
     */
    public function getRelated($source, $relationName, $query=null)
    {
        if (!$source) { return; }

        $sourceIsArray = false;

        $test = $source;
        if (is_array($test) || $test instanceof \Traversable) {
            $sourceIsArray = true;
            $test = $test[0];
        }

        $class = !is_object($test) ? $test : get_class($test);
        
        $meta = $this->mapper->getMeta($class);
        if (!isset($meta->relations[$relationName])) {
            throw new Exception("Unknown relation $relationName on $class");
        }
        
        $relation = $meta->relations[$relationName];
        $relator = $this->getRelator($relation);
        
        if ($query) {
            $query = $this->createQueryFromArgs([$query], 'Amiss\Sql\Query\Criteria');
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
     * Insert an object into the database, or values into a table
     * 
     * Supports the following signatures:
     *   insert($model)
     *   insert($className, $propertyValues);
     * 
     * @return int|null
     */
    public function insert($model, $query=null)
    {
        $meta = $this->mapper->getMeta($model);
        $object = null;

        if ($query) {
            $query = $query instanceof Query\Insert ? $query : new Query\Insert(['values'=>$query]);
        }
        else {
            $object = $model;
            $model = null;
            $query = new Query\Insert;
            $query->table = $meta->table;
        }

        if ($object && !$query->values) {
            $query->values = $this->mapper->fromObject($object, $meta, 'insert');
        }
        if (!$query->table) {
            $query->table = $meta->table;
        }

        list ($sql, $params) = $query->buildQuery();

        $stmt = $this->getConnector()->prepare($sql);
        $stmt->execute($params);
        
        $lastInsertId = null;
        if (($object && $meta->primary) || !$object) {
            $lastInsertId = $this->getConnector()->lastInsertId();
        }
        
        // we need to be careful with "lastInsertId": SQLLite generates one even without a PRIMARY
        if ($object && $meta->primary && $lastInsertId) {
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
     * Update an object in the database, or update a table by criteria.
     * 
     * @return void
     */
    public function update()
    {
        $args = func_get_args();
        $count = count($args);
        
        $first = array_shift($args);
        
        if (is_object($first)) {
        // Object update mode
            $class = get_class($first);
            $meta = $this->mapper->getMeta($class);
            $query = new Query\Update();
            $query->set = $this->mapper->fromObject($first, $meta, 'update');
            $query->where = $meta->getPrimaryValue($first);
        }

        elseif (is_string($first)) {
        // Table update mode
            if ($count < 2) {
                throw new \InvalidArgumentException("Query missing for table update");
            }
            $query = $this->createTableUpdateQuery($args);
            $class = $first;
            $meta = $this->mapper->getMeta($class);

            if (is_array($query->set)) {
                $query->set = (array) $this->mapper->fromProperties($query->set, $meta);
            }
        }
        else {
            throw new \InvalidArgumentException();
        }
        
        list ($sql, $params) = $query->buildQuery($meta);
        
        return $this->getConnector()->exec($sql, $params);
    }
    
    /**
     * Delete an object from the database, or delete objects from a table by criteria.
     * 
     * @return void
     */
    public function delete()
    {
        $args = func_get_args();
        $meta = null;
        $class = null;
        
        if (!$args) {
            throw new \InvalidArgumentException();
        }
        
        $first = array_shift($args);
        if (is_object($first)) {
            $class = $this->mapper->getMeta(get_class($first));
            $criteria = new Query\Criteria();
            $criteria->where = $class->getIndexValue($first);
        }
        else {
            if (!$args) {
                throw new \InvalidArgumentException("Cannot delete from table without a condition");
            }
            $class = $first;
            $criteria = $this->createQueryFromArgs($args, 'Amiss\Sql\Query\Criteria');
        }

        return $this->executeDelete($class, $criteria);
    }
    
    /** 
     * @param string The class name to delete
     * @param mixed The primary key
     */
    public function deleteById($class, $id)
    {
        return $this->delete($class, $this->createIdCriteria($class, $id));
    }

    /**
     * This is a hack to allow active record to intercept saving and fire events.
     * 
     * @param object The object to check
     * @return boolean
     */
    public function shouldInsert($object)
    {
        $meta = $this->mapper->getMeta(get_class($object));
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
    public function save($object)
    {
        $shouldInsert = $this->shouldInsert($object);
        
        if ($shouldInsert) {
            $this->insert($object);
        } else {
            $this->update($object);
        }
    }

    /**
     * Creates an array criteria for a primary key
     * @param string Class name to create criteria for
     * @param mixed Primary key
     * @throws Exception
     * @throws \InvalidArgumentException
     * @return array
     */
    protected function createIdCriteria($class, $id)
    {
        $meta = $this->mapper->getMeta($class);
        $primary = $meta->primary;
        if (!$primary) {
            throw new Exception("Can't use {$meta->class} by primary - none defined.");
        }
        if (!is_array($id)) {
            $id = array($id);
        }
        $where = array();
        
        foreach ($primary as $idx=>$p) {
            $idVal = isset($id[$p]) ? $id[$p] : (isset($id[$idx]) ? $id[$idx] : null);
            if (!$idVal) {
                throw new \InvalidArgumentException("Couldn't get ID value when getting {$meta->class} by id");
            }
            $where[$p] = $idVal;
        }
        
        return array('where'=>$where);
    }
    
    /**
     * @return \Amiss\Sql\Query\Update
     */
    protected function createTableUpdateQuery($args)
    {
        $query = null;
        if ($args[0] instanceof Query\Update) {
            $query = $args[0];
        }
        else {
            $cnt = count($args);
            if ($cnt == 1) {
                $query = new Query\Update($args[0]);
            }
            elseif ($cnt >= 2) {
                if (!is_array($args[0]) && !is_string($args[0])) {
                    throw new \InvalidArgumentException("Set must be an array or string");
                }
                $query = new Query\Update();
                $query->set = array_shift($args);
                $this->populateWhereAndParamsFromArgs($query, $args);
            }
            else {
                throw new \InvalidArgumentException("Unknown args count $cnt");
            }
        }
        return $query;
    }
    
    protected function executeDelete($meta, Query\Criteria $criteria)
    {
        if (!$meta instanceof Meta) {
            $meta = $this->mapper->getMeta($meta);
            if (!$meta instanceof Meta) {
                throw new \InvalidArgumentException();
            }
        }

        $table = $criteria->table ?: $meta->table;

        list ($whereClause, $whereParams) = $criteria->buildClause($meta);
        if (!$whereClause) {
            throw new \UnexpectedValueException("Empty where clause");
        }

        $sql = "DELETE FROM $table WHERE $whereClause";
        $stmt = $this->getConnector()->prepare($sql)->execute($whereParams);
    }
    
    /**
     * Parses remaining function arguments into a query object
     * @return \Amiss\Sql\Query\Criteria
     */
    protected function createQueryFromArgs($args, $type='Amiss\Sql\Query\Select')
    {
        if (!$args) {
            $query = new $type();
        }
        elseif ($args[0] instanceof $type) {
            $query = $args[0];
        }
        else {
            $query = new $type();
            $this->populateWhereAndParamsFromArgs($query, $args);
        }
        
        return $query;
    }
    
    /**
     * Allows functions to have different query syntaxes:
     * get('Name', 'pants=? AND foo=?', 'pants', 'foo')
     * get('Name', 'pants=:pants AND foo=:foo', array('pants'=>'pants', 'foo'=>'foo'))
     * get('Name', array('where'=>'pants=:pants AND foo=:foo', 'params'=>array('pants'=>'pants', 'foo'=>'foo')))
     */
    protected function populateWhereAndParamsFromArgs(Query\Criteria $query, $args)
    {
        if (count($args) == 1 && is_array($args[0])) {
        // Array criteria: $manager->get('class', ['where'=>'', 'params'=>'']);
            $query->populate($args[0]);
        }

        elseif (!is_array($args[0])) {
        // Args criteria: $manager->get('class', 'a=? AND b=?', 'a', 'b');
            $query->where = $args[0];
            if (isset($args[1]) && is_array($args[1])) {
                $query->params = $args[1];
            }
            elseif (isset($args[1])) {
                $query->params = array_slice($args, 1);
            }
        } 
        else {
            throw new \InvalidArgumentException("Couldn't parse arguments");
        }
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
