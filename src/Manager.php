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
	
	public function __construct($connector, Mapper $mapper)
	{
		if (is_array($connector)) 
			$connector = Connector::create($connector);
		
		$this->connector = $connector;
		$this->mapper = $mapper;
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
		
		$obj = null;
		
		while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
			if ($obj)
				throw new Exception("Query returned more than one row");
			
			$obj = $this->mapper->createObject($meta, $row, $criteria->args);
		}
		return $obj;
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
			$objects[] = $this->mapper->createObject($meta, $row, $criteria->args);
		}
		
		return $objects;
	}
	
	public function getByPk($class, $id)
	{
		$meta = $this->getMeta($class);
		$primary = $meta->primary;
		if (!$primary)
			throw new Exception("Can't retrieve {$meta->class} by primary - none defined.");
		
		return $this->get($meta->class, $primary.'=?', $id);
	}
	
	public function count($object, $criteria=null)
	{
		$criteria = $this->createSelectCriteria(array_slice(func_get_args(), 1));
		$meta = $this->getMeta($object);
		
		$table = $meta->table;
		
		list ($where, $params) = $criteria->buildClause();
		
		$field = $meta->primary ?: '*';
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
		
		$sourceIsArray = is_array($source) || $source instanceof \Traversable;
		if (!$sourceIsArray) $source = array($source);
		
		$class = !is_object($source[0]) ? $source[0] : get_class($source[0]);
		$meta = $this->getMeta($class);
		if (!isset($meta->relations[$relationName])) {
			throw new Exception("Unknown relation $relationName on $class");
		}
		
		$relation = $meta->relations[$relationName];
		
		// relation type
		if (isset($relation['one']))
			$type = 'one';
		elseif (isset($relation['many']))
			$type = 'many';
		else
			throw new Exception("Couldn't find relation type");
		
		$relatedMeta = $this->getMeta($relation[$type]);
		
		// prepare the relation's "on" field
		if ('one'==$type) {
			if (!isset($relation['on']))
				throw new Exception("One-to-one relation {$relationName} on class {$class} does not declare 'on' field");
			$on = $relation['on'];
		}
		else { // many
			$on = $meta->primary;
		}
		if (!is_array($on)) $on = array($on=>$on);
		
		// populate the 'on' with necessary data
		$relatedFields = $relatedMeta->getFields();
		foreach ($on as $l=>$r) {
			$on[$l] = $relatedFields[$r];
		}
		
		// find query values in source object(s)
		$resultIndex = array();
		$ids = array();
		foreach ($source as $idx=>$obj) {
			$key = array();
			
			foreach ($on as $l=>$r) {
				$lValue = !isset($relation['getter']) ? $obj->$l : call_user_func(array($obj, $relation['getter']));
				$key[] = $lValue;
				
				if (!isset($ids[$l])) {
					$ids[$l] = array('values'=>array(), 'rField'=>$r, 'param'=>$this->sanitiseParam($r['name']));
				}
				
				$ids[$l]['values'][$lValue] = true;
			}
			
			$key = !isset($key[1]) ? $key[0] : implode('|', $key);
			
			if (!isset($resultIndex[$key]))
				$resultIndex[$key] = array();
			
			$resultIndex[$key][$idx] = $obj;
		}
		
		// build query
		$criteria = new Criteria\Select;
		$where = array();
		foreach ($ids as $l=>$idInfo) {
			$rName = $idInfo['rField']['name'];
			$criteria->params[$rName] = array_keys($idInfo['values']);
			$where[] = '`'.str_replace('`', '', $rName).'` IN(:'.$idInfo['param'].')';
		}
		$criteria->where = implode(' AND ', $where);
		
		$list = $this->getList($relation[$type], $criteria);
		
		// prepare the result
		$result = null;
		if (!$sourceIsArray) {
			if ($list)
				$result = 'one' == $type ? current($list) : $list;
		}
		else {
			$result = array();
			foreach ($list as $related) {
				$key = array();
				
				foreach ($on as $l=>$r) {
					$name = $r['name'];
					$rValue = !isset($r['getter']) ? $related->$name : call_user_func(array($obj, $r['getter']));
					$key[] = $rValue;
				}
				$key = !isset($key[1]) ? $key[0] : implode('|', $key);
				
				foreach ($resultIndex[$key] as $idx=>$lObj) {
					if ('one' == $type) {
						$result[$idx] = $related;
					}
					elseif ('many' == $type) {
						if (!isset($result[$idx])) $result[$idx] = array();
						$result[$idx][] = $related;
					}
				}
			}
		}
		return $result;
	}
	
	public function insert()
	{
		$args = func_get_args();
		$count = count($args);
		$meta = null;
		$object = null;
		$class = null;
		$asTable = false;
		
		if ($count == 1) {
			$object = $args[0];
			$class = get_class($object);
			$meta = $this->getMeta($class);
			$values = $this->mapper->exportRow($meta, $object);
		}
		elseif ($count == 2) {
			$class = $args[0];
			$meta = $this->getMeta($class);
			$values = $args[1];
			$asTable = true;
		}
		
		if (!$values) {
			throw new Exception("No values found for class $class. Are your fields defined?");
		}
		
		$columns = array();
		$count = count($values);
		foreach ($values as $k=>$v) {
			if ($k[0]==':') {
				$k = substr($k, 1);
			}
			$columns[] = '`'.str_replace('`', '', $k).'`';
		}
		$sql = "INSERT INTO {$meta->table}(".implode(',', $columns).") VALUES(?".($count > 1 ? str_repeat(",?", $count-1) : '').")";
		
		$stmt = $this->getConnector()->prepare($sql);
		++$this->queries;
		$stmt->execute(array_values($values));
		
		$lastInsertId = null;
		
		if (($object && $meta->primary) || $asTable)
			$lastInsertId = $this->getConnector()->lastInsertId();
		
		if ($object && $meta->primary && $lastInsertId)
			$this->mapper->setProperty($meta, $object, $meta->primary, $lastInsertId);
		
		return $lastInsertId;
	}
	
	public function update()
	{
		$args = func_get_args();
		$count = count($args);
		
		$first = array_shift($args);
		if (is_object($first)) {
			$criteria = $this->createObjectUpdateCriteria($first, $args);
			$objectName = get_class($first);
		}
		elseif (is_string($first)) {
			// FIXME: improve text
			if ($count < 2)
				throw new \InvalidArgumentException();
			
			$criteria = $this->createTableUpdateCriteria($first, $args);
			$objectName = $first;
		}
		else {
			throw new \InvalidArgumentException();
		}
		
		return $this->executeUpdate($objectName, $criteria);
	}
	
	public function delete()
	{
		$args = func_get_args();
		$count = count($args);
		
		if ($count < 1)
			throw new \InvalidArgumentException();
		elseif ($count == 1) {
			if (!$meta->primary)
				throw new Exception("Can't update {$meta->class} without passing criteria as it doesn't define a primary");
			// FIXME: this shit needs a good cleanup
			$args[1] = $meta->primary;
			++$count;
		}
		
		$first = array_shift($args);
		if ($args[0] instanceof Criteria\Query) {
			$criteria = $args[0];
		}
		else {
			// FIXME: pretty ugly hack to make deleting by object property work
			if (is_object($first) && $count==2 && is_string($args[0]) && property_exists($first, $args[0])) {
				$args = array(array('where'=>array($args[0]=>$first->{$args[0]})));
			}
			
			$criteria = new Criteria\Query;
			$this->populateQueryCriteria($criteria, $args);
		}
		
		$objectName = is_object($first) ? get_class($first) : $first;
		
		return $this->executeDelete($objectName, $criteria);
	}
	
	/**
	 * Hack to allow active record to intercept saving and fire events
	 */
	public function shouldInsert($object)
	{
		$meta = $this->getMeta(get_class($object));
		if (!$meta->primary)
			throw new Exception("Manager requires a primary if you want to call 'save'.");
		
		$id = $this->mapper->getProperty($meta, $object, $meta->primary);
		
		return $id == false;
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
	
	protected function createObjectUpdateCriteria($object, $args)
	{
		$meta = $this->getMeta(get_class($object));
		
		$uc = new Criteria\Update();
		$uc->set = $this->mapper->exportRow($meta, $object);
		
		if (count($args) < 1) {
			if (!$meta->primary)
				throw new Exception("Can't update {$meta->class} without passing criteria as it doesn't define a primary");
			$args[0] = array($meta->primary=>$this->mapper->getProperty($meta, $object, $meta->primary));
		}
		
		if (is_string($args[0])) {
			if (isset($uc->set[$args[0]])) {
				$uc->where = array($args[0]=>$uc->set[$args[0]]);
			}
			else {
				$this->populateQueryCriteria($uc, $args);
			}
		}
		elseif (is_array($args[0]))
			$uc->where = $args[0];
		else
			throw new \InvalidArgumentException("Couldn't understand args");
		
		return $uc;
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
	
	protected function sanitiseParam($id) 
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
		$items = is_array($path) ? $path : explode('/', $path);
		$one = count($items)==1;
		
		foreach ($objects as $o) {
			if ($one) {
				$item = $o->{$items[0]};
			}
			else {
				$current = $o;
				foreach ($items as $i) {
					$current = $current->$i;
				}
				$item = $current;
			}
			if (is_array($item)) {
				foreach ($item as $i) $array[] = $i;
			}
			else $array[] = $item;
		}
		return $array;
	}
	
	public function execute($stmt, $params=null)
	{
		if (is_string($stmt)) 
			$stmt = $this->getConnector()->prepare($stmt);
		
		if (!$stmt instanceof \PDOStatement)
			throw new \InvalidArgumentException();
		
		if ($params) {
			foreach ($params as $k=>$v) {
				if (is_numeric($k)) ++$k;
				$stmt->bindValue($k, $v);
			}
		}
		++$this->queries;
		$stmt->execute();
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
