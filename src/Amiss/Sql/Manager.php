<?php
namespace Amiss\Sql;

use Amiss\Exception;
use Amiss\Mapper;

/**
 * Amiss query manager. This is the core of Amiss' functionality.
 * 
 * @package Manager
 */
class Manager
{
    const INDEX_DUPE_CONTINUE = 0;
    
    const INDEX_DUPE_FAIL = 1;
    
    /**
     * Database connector
     * @var Amiss\Sql\Connector|\PDO|PDO-esque
     */
    public $connector;
    
    /**
     * Number of queries performed by the manager.
     * May not be accurate.
     * @var int
     */
    public $queries = 0;
    
    /**
     * @var Amiss\Mapper
     */
    public $mapper;
    
    /**
     * Relators used by getRelated
     * @var (Amiss\Sql\Relator|callable)[]
     */
    public $relators;
    
    /**
     * Configuration parameter can contain the following keys:
     * 
     *  - `mapper`
     *      An instance of `Amiss\Mapper`. If not passed, the default
     *      of `Amiss\Mapper\Note` is used with the `Amiss\Sql\TypeSet` TypeSet.
     * 
     * @param Amiss\Sql\Connector|\PDO|array  Database connector
     * @param array  Configuration
     */
    public function __construct($connector, $mapper)
    {
        if (is_array($connector)) 
            $connector = Connector::create($connector);
        
        $this->connector = $connector;
        $this->mapper = $mapper;
    }
    
    /**
     * Get the database connector
     * 
     * @return \Amiss\Sql\Connector|\PDO
     */
    public function getConnector()
    {
        return $this->connector;
    }
    
    /**
     * Get the table mappping metadata for a class
     * 
     * @param string Class name
     * @return \Amiss\Meta 
     */
    public function getMeta($class)
    {
        return $this->mapper->getMeta($class);
    }
    
    /**
     * Get a single object from the database
     * 
     * @return object
     */
    public function get($class)
    {
        $criteria = $this->createQueryFromArgs(array_slice(func_get_args(), 1));
        $meta = $this->getMeta($class);
        
        list ($limit, $offset) = $criteria->getLimitOffset();
        if ($limit && $limit != 1)
            throw new Exception("Limit must be one or zero");
        
        list ($query, $params) = $criteria->buildQuery($meta);
        
        $stmt = $this->getConnector()->prepare($query);
        $this->execute($stmt, $params);
        
        $object = null;
        
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if ($object)
                throw new Exception("Query returned more than one row");
            
            $object = $this->mapper->toObject($meta, $row, $criteria->args);
        }
        return $object;
    }
    
    /**
     * Get a list of objects from the database
     * 
     * @return object[]
     */
    public function getList($class)
    {
        $criteria = $this->createQueryFromArgs(array_slice(func_get_args(), 1));
        $meta = $this->getMeta($class);
        
        list ($query, $params) = $criteria->buildQuery($meta);
        $stmt = $this->getConnector()->prepare($query);
        $this->execute($stmt, $params);
        
        $objects = array();
    
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $object = $this->mapper->toObject($meta, $row, $criteria->args);
            $objects[] = $object;
        }
        
        return $objects;
    }
    
    /**
     * Get a single object from the database by primary key
     * 
     * @return object
     */
    public function getById($class, $id, $args=null)
    {
        $criteria = $this->createIdCriteria($class, $id);
        if ($args)
            $criteria['args'] = $args; 
        return $this->get($class, $criteria);
    }
    
    /*
     * Get a single object from the database by primary key
     * 
     * @deprecated Use getById
     */
    public function getByPk($class, $id, $args=null)
    {
        return $this->getById($class, $id, $args);
    }
    
    /**
     * Count the objects in the database that match the criteria
     * 
     * @return int
     */
    public function count($class, $criteria=null)
    {
        $criteria = $this->createQueryFromArgs(array_slice(func_get_args(), 1));
        $meta = $this->getMeta($class);
        
        $table = $meta->table;
        
        list ($where, $params) = $criteria->buildClause($meta);
        
        $field = '*';
        if ($meta->primary && count($meta->primary) == 1) {
            $metaField = $meta->getField($meta->primary[0]);
            $field = $metaField['name'];
        }
        
        $query = "SELECT COUNT($field) FROM $table "
            .($where  ? "WHERE $where" : '')
        ;
        
        $stmt = $this->getConnector()->prepare($query);
        $this->execute($stmt, $params);
        return (int)$stmt->fetchColumn();
    }
    
    /**
     * Retrieve related objects from the database and assign them to the source
     * 
     * @param object|array Source objects to assign relations for
     * @param string The name of the relation to assign
     * @return void
     */
    public function assignRelated($source, $relationName)
    {
        $result = $this->getRelated($source, $relationName);
        
        if ($result) {
            $sourceIsArray = is_array($source) || $source instanceof \Traversable;
            if (!$sourceIsArray) {
                $source = array($source);
                $result = array($result);
            }
            
            $meta = $this->getMeta(get_class($source[0]));
            $relation = $meta->relations[$relationName];
            
            foreach ($result as $idx=>$item) {
                if (!isset($relation['setter']))
                    $source[$idx]->{$relationName} = $item;
                else
                    call_user_func(array($source[$idx], $relation['setter']), $item);
            }
        }
    }
    
    /**
     * Get related objects from the database
     * 
     * @param object|array Source objects to assign relations for
     * @param string The name of the relation to assign
     * @param criteria Optional criteria to limit the result
     * @return object[]
     */
    public function getRelated($source, $relationName, $criteria=null)
    {
        if (!$source) return;
        
        $test = $source;
        if (is_array($test) || $test instanceof \Traversable)
            $test = $test[0];
        
        $class = !is_object($test) ? $test : get_class($test);
        
        $meta = $this->getMeta($class);
        if (!isset($meta->relations[$relationName])) {
            throw new Exception("Unknown relation $relationName on $class");
        }
        
        $relation = $meta->relations[$relationName];
        
        if (!isset($this->relators[$relation[0]])) {
            throw new Exception("Relator {$relation[0]} not found");
        }
        
        $relator = $this->relators[$relation[0]];
        if (!$relator instanceof Relator) {
            $relator = $this->relators[$relation[0]] = call_user_func($relator, $this);
        }
        
        $query = null;
        if ($criteria) {
            $query = $this->createQueryFromArgs(array_slice(func_get_args(), 2), 'Amiss\Sql\Criteria\Query');
        }
        
        return $relator->getRelated($source, $relationName, $query);
    }
    
    /**
     * Insert an object into the database, or values into a table
     * 
     * @return int|null
     */
    public function insert()
    {
        $args = func_get_args();
        $count = count($args);
        $meta = null;
        $object = null;
        
        if ($count == 1) {
            $object = $args[0];
            $meta = $this->getMeta(get_class($object));
            $values = $this->mapper->fromObject($meta, $object, 'insert');
        }
        elseif ($count == 2) {
            $meta = $this->getMeta($args[0]);
            $values = $args[1];
        }
        
        if (!$values)
            throw new Exception("No values found for class {$meta->class}. Are your fields defined?");
        
        $columns = array();
        $count = count($values);
        foreach ($values as $k=>$v) {
            $columns[] = '`'.str_replace('`', '', $k).'`';
        }
        $sql = "INSERT INTO {$meta->table}(".implode(',', $columns).") VALUES(?".($count > 1 ? str_repeat(",?", $count-1) : '').")";
        
        $stmt = $this->getConnector()->prepare($sql);
        ++$this->queries;
        $stmt->execute(array_values($values));
        
        $lastInsertId = null;
        if (($object && $meta->primary) || !$object)
            $lastInsertId = $this->getConnector()->lastInsertId();
        
        // we need to be careful with "lastInsertId": SQLLite generates one even without a PRIMARY
        if ($object && $meta->primary && $lastInsertId) {
            if (($count=count($meta->primary)) != 1)
                throw new Exception("Last insert ID $lastInsertId found for class {$meta->class}. Expected 1 primary field, but class defines {$count}");
            
            $field = $meta->getField($meta->primary[0]);
            $handler = $this->mapper->determineTypeHandler($field['type']);
            
            if ($handler instanceof \Amiss\Type\Identity) {
                $generated = $handler->handleDbGeneratedValue($lastInsertId);
                if ($generated) {
                    // skip using populateObject - we don't need the type handler stack because we 
                    // already used one to handle the value
                    if (isset($field['getter']))
                        $object->{$field['setter']}($generated);
                    else
                        $object->{$meta->primary[0]} = $generated;
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
            $class = get_class($first);
            $meta = $this->getMeta($class);
            $criteria = new Criteria\Update();
            $criteria->set = $this->mapper->fromObject($meta, $first, 'update');
            $criteria->where = $meta->getPrimaryValue($first);
        }
        elseif (is_string($first)) {
            // FIXME: improve text
            if ($count < 2)
                throw new \InvalidArgumentException();
            
            $criteria = $this->createTableUpdateCriteria($args);
            $class = $first;
            $meta = $this->getMeta($class);
        }
        else {
            throw new \InvalidArgumentException();
        }
        
        list ($sql, $params) = $criteria->buildQuery($meta);
        
        $stmt = $this->getConnector()->prepare($sql);
        ++$this->queries;
        $stmt->execute($params);
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
        
        if (!$args) throw new \InvalidArgumentException();
        
        $first = array_shift($args);
        if (is_object($first)) {
            $meta = $this->getMeta(get_class($first));
            $class = $meta->class;
            $criteria = new Criteria\Query();
            $criteria->where = $meta->getPrimaryValue($first);
        }
        else {
            if (!$args) throw new \InvalidArgumentException("Cannot delete from table without a condition");
            
            $class = $first;
            $criteria = $this->createQueryFromArgs($args, 'Amiss\Sql\Criteria\Query');
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
     * @deprecated Use deleteById
     */
    public function deleteByPk($class, $pk)
    {
        return $this->deleteById($class, $pk);
    }
    
    /**
     * This is a hack to allow active record to intercept saving and fire events.
     * 
     * @param object The object to check
     * @return boolean
     */
    public function shouldInsert($object)
    {
        $meta = $this->getMeta(get_class($object));
        $nope = false;
        if (!$meta->primary || count($meta->primary) > 1) {
            $nope = true;
        }
        else {
            $field = $meta->getField($meta->primary[0]);
            if ($field['type'] != 'autoinc')
                $nope = true;
        }
        if ($nope)
            throw new Exception("Manager requires a single-column autoincrement primary if you want to call 'save'.");
        
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
        
        if ($shouldInsert)
            $this->insert($object);
        else
            $this->update($object);
    }

    
    /**
     * Iterate over an array of objects and returns an array of objects indexed by a property
     * 
     * @param array The list of objects to index
     * @param string The property to index by
     * @param integer Index mode
     * @return array
     */
    public function indexBy($list, $property, $mode=self::INDEX_DUPE_CONTINUE)
    {
        $index = array();
        foreach ($list as $i) {
            if ($mode === self::INDEX_DUPE_FAIL && isset($index[$i->$property]))
                throw new \UnexpectedValueException("Duplicate value for property $property");
            $index[$i->$property] = $i;
        }
        return $index;
    }
    
    /**
     * Create a one-dimensional associative array from a list of objects, or a list of 2-tuples.
     * 
     * @param object[]|array $list
     * @param string $keyProperty
     * @param string $valueProperty
     * @return array
     */
    public function keyValue($list, $keyProperty=null, $valueProperty=null)
    {
        $index = array();
        foreach ($list as $i) {
            if ($keyProperty) {
                if (!$valueProperty) 
                    throw new \InvalidArgumentException("Must set value property if setting key property");
                $index[$i->$keyProperty] = $i->$valueProperty;
            }
            else {
                $key = current($i);
                next($i);
                $value = current($i);
                $index[$key] = $value;
            }
        }
        return $index;
    }
    
    /**
     * Retrieve all object child values through a property path.
     * 
     * @param object[] $objects
     * @param string|array $path
     * @return array
     */
    public function getChildren($objects, $path)
    {
        $array = array();
        if (!is_array($path)) $path = explode('/', $path);
        if (!is_array($objects)) $objects = array($objects);
        
        $count = count($path);
        
        foreach ($objects as $o) {
            $value = $o->{$path[0]};
            
            if (is_array($value) || $value instanceof \Traversable)
                $array = array_merge($array, $value);
            elseif ($value !== null)
                $array[] = $value;
        }
        
        if ($count > 1) {
            $array = $this->getChildren($array, array_slice($path, 1));
        }
        
        return $array;
    }
    
    /**
     * @param string|\PDOStatement $stmt
     * @param array $params
     * @return \PDOStatement
     * @throws \InvalidArgumentException
     */
    public function execute($stmt, $params=null)
    {
        if (is_string($stmt)) 
            $stmt = $this->getConnector()->prepare($stmt);
        
        if (!isset($stmt->queryString))
            throw new \InvalidArgumentException("Statement didn't look like a PDOStatement");
        
        ++$this->queries;
        $stmt->execute($params);
        
        return $stmt;
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
        $meta = $this->getMeta($class);
        $primary = $meta->primary;
        if (!$primary)
            throw new Exception("Can't delete retrieve {$meta->class} by primary - none defined.");
        
        if (!is_array($id)) $id = array($id);
        $where = array();
        
        foreach ($primary as $idx=>$p) {
            $idVal = isset($id[$p]) ? $id[$p] : (isset($id[$idx]) ? $id[$idx] : null);
            if (!$idVal)
                throw new \InvalidArgumentException("Couldn't get ID value when getting {$meta->class} by id");
            $where[$p] = $idVal;
        }
        
        return array('where'=>$where);
    }
    
    /**
     * Create criteria to update a table
     * @param array Arguments to create criteria from
     * @return \Amiss\Sql\Criteria\Update
     */
    protected function createTableUpdateCriteria($args)
    {
        $criteria = null;
        if ($args[0] instanceof Criteria\Update) {
            $criteria = $args[0];
        }
        else {
            $cnt=count($args);
            if ($cnt == 1) {
                $criteria = new Criteria\Update($args[0]);
            }
            elseif ($cnt >= 2 && $cnt < 4) {
                if (!is_array($args[0]))
                    throw new \InvalidArgumentException("Set must be an array");
                $criteria = new Criteria\Update();
                $criteria->set = array_shift($args);
                $this->populateWhereAndParamsFromArgs($criteria, $args);
            }
            else {
                throw new \InvalidArgumentException("Unknown args count $cnt");
            }
        }
        return $criteria;
    }
    
    /**
     * Execute a delete query
     * @param string The class to delete
     * @param Criteria\Query The criteria to use to build the where clause
     */
    protected function executeDelete($class, Criteria\Query $criteria)
    {
        $meta = $this->getMeta($class);
        $table = $meta->table;
        
        list ($whereClause, $whereParams) = $criteria->buildClause($meta);
        
        $sql = "DELETE FROM $table WHERE $whereClause";
        
        $stmt = $this->getConnector()->prepare($sql);
        ++$this->queries;
        $stmt->execute($whereParams);
    }
    
    /**
     * Parses remaining function arguments into a query object
     * @param array Leftover function arguments
     * @param string Class name of query to instantiate
     * @return \Amiss\Sql\Criteria\Query
     */
    protected function createQueryFromArgs($args, $type='Amiss\Sql\Criteria\Select')
    {
        if (!$args) {
            $criteria = new $type();
        }
        elseif ($args[0] instanceof $type) {
            $criteria = $args[0];
        }
        else {
            $criteria = new $type();
            $this->populateWhereAndParamsFromArgs($criteria, $args);
        }
        
        return $criteria;
    }
    
    /**
     * Populates the "where" clause of a criteria object
     * 
     * Allows functions to have different query syntaxes:
     * get('Name', 'pants=? AND foo=?', 'pants', 'foo')
     * get('Name', 'pants=:pants AND foo=:foo', array('pants'=>'pants', 'foo'=>'foo'))
     * get('Name', array('where'=>'pants=:pants AND foo=:foo', 'params'=>array('pants'=>'pants', 'foo'=>'foo')))
     * 
     * @param Criteria\Query The criteria to populate
     * @param array The arguments to use to populate the criteria
     */
    protected function populateWhereAndParamsFromArgs(Criteria\Query $criteria, $args)
    {
        if (count($args)==1 && is_array($args[0])) {
            $criteria->populate($args[0]);
        }
        elseif (!is_array($args[0])) {
            $criteria->where = $args[0];
            if (isset($args[1]) && is_array($args[1])) {
                $criteria->params = $args[1];
            }
            elseif (isset($args[1])) {
                $criteria->params = array_slice($args, 1);
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
