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
	
	public $mapper;
	
	public $relators = array();
	
	public function __construct($connector, Mapper $mapper)
	{
		if (is_array($connector)) 
			$connector = Connector::create($connector);
		
		$this->connector = $connector;
		$this->mapper = $mapper;
		
		$this->relators['one'] = $this->relators['many'] = new Relator\OneMany;
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
		$criteria = $this->createSelectCriteria(array_slice(func_get_args(), 1));
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
		$criteria = $this->createSelectCriteria(array_slice(func_get_args(), 1));
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
		$meta = $this->getMeta($class);
		$primary = $meta->primary;
		if (!$primary)
			throw new Exception("Can't retrieve {$meta->class} by primary - none defined.");
		
		if (!is_array($id)) $id = array($id);
		$where = array();
		
		foreach ($primary as $idx=>$p) {
			$idVal = isset($id[$p]) ? $id[$p] : (isset($id[$idx]) ? $id[$idx] : null);
			if (!$idVal)
				throw new \InvalidArgumentException("Couldn't get ID value when getting {$meta->class} by pk");
			$where[$p] = $idVal;
		}
		
		$criteria = array(
			'where'=>$where,
			'args'=>$args ?: array(),
		);
		
		return $this->get($meta->class, $criteria);
	}
	
	public function count($class, $criteria=null)
	{
		$criteria = $this->createSelectCriteria(array_slice(func_get_args(), 1));
		$meta = $this->getMeta($class);
		
		$table = $meta->table;
		
		list ($where, $params) = $criteria->buildClause();
		
		$field = '*';
		if ($meta->primary) {
			$fields = $meta->getFields();
			$field = array();
			foreach ($meta->primary as $p) {
				$field[] = $fields[$p]['name'];
			}
			$field = implode(', ', $field);
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
	
	public function getRelated($source, $relationName)
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
		
		// FIXME: this hack expects the relation type to be the first key in the
		// relation definition. The internal relation definition needs to be 
		// cleaned up.
		$type = key($relation);
		
		if (!isset($this->relators[$type]))
			throw new Exception("Relator $type not found");
		
		return $this->relators[$type]->getRelated($this, $type, $source, $relationName);
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
			$class = $first;
			if ($args[0] instanceof Criteria\Query)
				$criteria = $args[0];
			else {
				$criteria = new Criteria\Query;
				$this->populateQueryCriteria($criteria, $args);
			}
		}
		
		return $this->executeDelete($class, $criteria);
	}
	
	/**
	 * Hack to allow active record to intercept saving and fire events
	 */
	public function shouldInsert($object)
	{
		$meta = $this->getMeta(get_class($object));
		if (!$meta->primary)
			throw new Exception("Manager requires a primary if you want to call 'save'.");
		
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
				$this->populateQueryCriteria($criteria, $args);
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
	
	protected function createSelectCriteria($args)
	{
		if (!$args) {
			$criteria = new Criteria\Select();
		}
		elseif ($args[0] instanceof Criteria\Select) {
			$criteria = $args[0];
		}
		else {
			$criteria = new Criteria\Select();
			$this->populateQueryCriteria($criteria, $args);
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
	 */
	protected function populateQueryCriteria(Criteria\Query $criteria, $args)
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
