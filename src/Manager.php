<?php

namespace Amiss;

const INDEX_DUPE_CONTINUE = 0;
const INDEX_DUPE_FAIL = 1;

class Manager
{
	/**
	 * @var Amiss\Connector (or PDO, or anything PDO-compatible)
	 */
	public $connector;
	
	public $queries = 0;
	
	/**
	 * @var Amiss\Meta
	 */
	protected $meta = array();
	
	/**
	 * @var Amiss\Mapper
	 */
	public $mapper;
	
	public $relators;
	
	public function __construct($connector, Mapper $mapper, $relators=null)
	{
		if (is_array($connector)) 
			$connector = Connector::create($connector);
		
		$this->connector = $connector;
		$this->mapper = $mapper;
		
		if ($relators===null) {
			$this->relators = array();
			$this->relators['one'] = $this->relators['many'] = new Relator\OneMany($this);
			$this->relators['assoc'] = new Relator\Association($this);
		}
		else {
			$this->relators = $relators;
		}
	}
	
	/**
	 * @return \PDO
	 */
	public function getConnector()
	{
		return $this->connector;
	}
	
	public function getMeta($object)
	{
		if (isset($this->meta[$object]))
			return $this->meta[$object];
		
		$meta = $this->mapper->getMeta($object);
		
		$this->meta[$object] = $meta;
		
		return $meta;
	}
	
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
			
			$object = $this->mapper->createObject($meta, $row, $criteria->args);
			$this->mapper->populateObject($meta, $object, $row);
		}
		return $object;
	}

	public function getList($class)
	{
		$criteria = $this->createQueryFromArgs(array_slice(func_get_args(), 1));
		$meta = $this->getMeta($class);
		
		list ($query, $params) = $criteria->buildQuery($meta);
		
		$stmt = $this->getConnector()->prepare($query);
		$this->execute($stmt, $params);
		
		$objects = array();
	
		while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
			$object = $this->mapper->createObject($meta, $row, $criteria->args);
			$this->mapper->populateObject($meta, $object, $row);
			$objects[] = $object;
		}
		
		return $objects;
	}
	
	public function getByPk($class, $id, $args=null)
	{
		$criteria = $this->createPkCriteria($class, $id);
		if ($args)
			$criteria['args'] = $args; 
		return $this->get($class, $criteria);
	}
	
	public function count($class, $criteria=null)
	{
		$criteria = $this->createQueryFromArgs(array_slice(func_get_args(), 1));
		$meta = $this->getMeta($class);
		
		$table = $meta->table;
		
		list ($where, $params) = $criteria->buildClause();
		
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
		
		if (!isset($this->relators[$relation[0]]))
			throw new Exception("Relator {$relation[0]} not found");
		
		$query = null;
		if ($criteria) {
			$query = $this->createQueryFromArgs(array_slice(func_get_args(), 2), 'Amiss\Criteria\Query');
		}
		
		return $this->relators[$relation[0]]->getRelated($source, $relationName, $query);
	}
	
	public function insert()
	{
		$args = func_get_args();
		$count = count($args);
		$meta = null;
		$object = null;
		
		if ($count == 1) {
			$object = $args[0];
			$meta = $this->getMeta(get_class($object));
			$values = $this->mapper->exportRow($meta, $object);
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
		
		if ($object && $meta->primary && $lastInsertId) {
			if (($count=count($meta->primary)) != 1)
				throw new Exception("Last insert ID $lastInsertId found for class {$meta->class}. Expected 1 primary field, but class defines {$count}");
			
			$this->mapper->populateObject($meta, $object, array($meta->primary[0]=>$lastInsertId));
		}
		
		return $lastInsertId;
	}
	
	public function update()
	{
		$args = func_get_args();
		$count = count($args);
		
		$first = array_shift($args);
		
		if (is_object($first)) {
			$class = get_class($first);
			$meta = $this->getMeta($class);
			$criteria = new Criteria\Update();
			$criteria->set = $this->mapper->exportRow($meta, $first);
			$criteria->where = $meta->getPrimaryValue($first);
		}
		elseif (is_string($first)) {
			// FIXME: improve text
			if ($count < 2)
				throw new \InvalidArgumentException();
			
			$criteria = $this->createTableUpdateCriteria($first, $args);
			$class = $first;
		}
		else {
			throw new \InvalidArgumentException();
		}
		
		return $this->executeUpdate($class, $criteria);
	}
	
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
			$criteria = $this->createQueryFromArgs($args, 'Amiss\Criteria\Query');
		}
		
		return $this->executeDelete($class, $criteria);
	}
	
	public function deleteByPk($class, $pk)
	{
		return $this->delete($class, $this->createPkCriteria($class, $pk));
	}
	
	/**
	 * Hack to allow active record to intercept saving and fire events
	 */
	public function shouldInsert($object)
	{
		$meta = $this->getMeta(get_class($object));
		$nope = false;
		if (!$meta->primary || count($meta->primary) > 1)
			$nope = true;
		else {
			$field = $meta->getField($meta->primary[0]);
			if ($field['type'] != 'autoinc')
				$nope = true;
		}
		if ($nope) throw new Exception("Manager requires a single-column autoincrement primary if you want to call 'save'.");
		
		$prival = $meta->getPrimaryValue($object);
		return $prival == false;
	}
	
	public function save($object)
	{
		$shouldInsert = $this->shouldInsert($object);
		
		if ($shouldInsert)
			$this->insert($object);
		else
			$this->update($object);
	}
	
	protected function createPkCriteria($class, $pk)
	{
		$meta = $this->getMeta($class);
		$primary = $meta->primary;
		if (!$primary)
			throw new Exception("Can't delete retrieve {$meta->class} by primary - none defined.");
		
		if (!is_array($pk)) $pk = array($pk);
		$where = array();
		
		foreach ($primary as $idx=>$p) {
			$idVal = isset($pk[$p]) ? $pk[$p] : (isset($pk[$idx]) ? $pk[$idx] : null);
			if (!$idVal)
				throw new \InvalidArgumentException("Couldn't get ID value when getting {$meta->class} by pk");
			$where[$p] = $idVal;
		}
		
		return array('where'=>$where);
	}

	protected function createTableUpdateCriteria($table, $args)
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
	
	protected function executeUpdate($objectName, Criteria\Update $update)
	{
		$meta = $this->getMeta($objectName);
		$table = $meta->table;
		
		list ($setClause,   $setParams)   = $update->buildSet();
		list ($whereClause, $whereParams) = $update->buildClause();
		
		$params = array_merge($setParams, $whereParams);
		if (count($params) != count($setParams) + count($whereParams)) {
			$intersection = array_intersect(array_keys($setParams), array_keys($whereParams));
			throw new Exception("Param overlap between set and where clause. Duplicated keys: ".implode(', ', $intersection));
		}
		
		if (!$whereClause)
			throw new \InvalidArgumentException("No where clause specified for table update. Explicitly specify 1=1 as the clause if you meant to do this.");
		
		$sql = "UPDATE $table SET $setClause WHERE $whereClause";
		$stmt = $this->getConnector()->prepare($sql);
		++$this->queries;
		$stmt->execute($params);
	}
	
	protected function executeDelete($objectName, Criteria\Query $criteria)
	{
		$meta = $this->getMeta($objectName);
		$table = $meta->table;
		
		list ($whereClause, $whereParams) = $criteria->buildClause();
		
		$sql = "DELETE FROM $table WHERE $whereClause";
		
		$stmt = $this->getConnector()->prepare($sql);
		++$this->queries;
		$stmt->execute($whereParams);
	}
	
	public function sanitiseParam($id) 
	{
		return preg_replace('/[^A-z0-9_]/', '', $id);
	}
	
	public function indexBy($property, $list, $mode=INDEX_DUPE_CONTINUE)
	{
		$index = array();
		foreach ($list as $i) {
			if ($mode === INDEX_DUPE_FAIL && isset($index[$i->$property]))
				throw new \UnexpectedValueException("Duplicate value for property $property");
			$index[$i->$property] = $i;
		}
		return $index;
	}
	
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
	
	public function getChildren($objects, $path)
	{
		$array = array();
		if (!is_array($path)) $path = explode('/', $path);
		if (!is_array($objects)) $objects = array($objects);
		
		$count = count($path);
		
		foreach ($objects as $o) {
			$value = $o->{$path[0]};
			
			if (is_array($value))
				$array = array_merge($array, $value);
			elseif ($value !== null)
				$array[] = $value;
		}
		
		if ($count > 1) {
			$array = $this->getChildren($array, array_slice($path, 1));
		}
		
		return $array;
	}
	
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
	
	protected function createQueryFromArgs($args, $type='Amiss\Criteria\Select')
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
	 * The class name 'Name' argument should be lopped off before this gets called. 
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
	
	public function __get($name)
	{
		throw new \BadMethodCallException("$name does not exist");
	}
	
	public function __set($name, $value)
	{
		throw new \BadMethodCallException("$name does not exist");
	}
	
	public function __isset($name)
	{
		throw new \BadMethodCallException("$name does not exist");
	}
	
	public function __unset($name)
	{
		throw new \BadMethodCallException("$name does not exist");
	}
}
