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
	
	public function getRelated($source, $object, $on)
	{
		$ids = array();
		$into = null;
		
		if (is_array($source)) 
			list($source, $into) = $source;
		
		$isArray = is_array($source);
		
		if (!$isArray) $source = array($source);
		if (is_string($on)) $on = array($on=>$on);
		
		$rIndex = array();
		foreach ($source as $obj) {
			$key = array();
			foreach ($on as $l=>$r) {
				if (is_numeric($l)) $l = $r;
				$key[] = $obj->$l;
				
				if (!isset($ids[$l])) {	
					$ids[$l] = array('values'=>array(), 'r'=>$r, 'param'=>$this->sanitiseParam($r));
				}
				$ids[$l]['values'][] = $obj->$l;
			}
			$str = implode("|", $key);
			
			if (!isset($rIndex[$str])) $rIndex[$str] = array();
			
			if ($into) {
				$rIndex[$str][] = &$obj->$into;
			} 
		}
		
		$criteria = new Criteria\Select;
		$where = array();
		foreach ($ids as $l=>$meta) {
			$criteria->params[$meta['r']] = $meta['values'];
			$where[] = '`'.str_replace('`', '', $meta['r']).'` IN(:'.$meta['param'].')';
		}
		$criteria->where = implode(' AND ', $where);
		
		$list = $this->getList($object, $criteria);
		
		if ($into) {
			$index = array();
			foreach ($list as $item) {
				$key = array();
				foreach ($on as $l=>$r) {
					$key[] = $item->$r;
				}
				$key = implode('|', $key);
				if (isset($rIndex[$key])) {
					foreach ($rIndex[$key] as &$x) {
						$x = $item;
					}
				}
			}
		}
		else {
			if ($isArray) return $list;
			else return current($list);
		}
	}
	
	/**
	 * Fetches a list relationship for an object.
	 * 
	 * FIXME: lots of copypasta between this and getRelated
	 */
	public function getRelatedList($source, $object, $on)
	{
		$ids = array();
		$into = null;
		
		if (is_array($source)) 
			list($source, $into) = $source;
		
		$isArray = is_array($source);
		
		if (!$isArray)
			$source = array($source);
		elseif (!$into) 
			throw new \Exception("Can't pass an array without using into");
		
		if (is_string($on)) $on = array($on=>$on);
		
		$popIndex = array();
		foreach ($source as $obj) {
			$key = array();
			foreach ($on as $l=>$r) {
				if (is_numeric($l)) $l = $r;
				$key[] = $obj->$l;
				
				if (!isset($ids[$l])) {	
					$ids[$l] = array('values'=>array(), 'r'=>$r, 'param'=>$this->sanitiseParam($r));
				}
				$ids[$l]['values'][] = $obj->$l;
			}
			if ($into) {
				$obj->$into = array();
				$popIndex[implode("|", $key)] = &$obj->$into;
			} 
		}
		
		$criteria = new Criteria\Select;
		$where = array();
		foreach ($ids as $l=>$meta) {
			$criteria->params[$meta['r']] = $meta['values'];
			$where[] = '`'.str_replace('`', '', $meta['r']).'` IN(:'.$meta['param'].')';
		}
		$criteria->where = implode(' AND ', $where);
		
		$list = $this->getList($object, $criteria);
		
		if ($into) {
			foreach ($list as $item) {
				$key = array();
				foreach ($on as $l=>$r) {
					$key[] = $item->$r;
				}
				$key = implode('|', $key);
				if (!isset($popIndex[$key]))
					$popIndex[$key] = array();
				$popIndex[$key][] = $item;
			}
		}
		else {
			return $list;
		}
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
		
		if ($count < 2)
			throw new \InvalidArgumentException();
		
		$first = array_shift($args);
		if (is_object($first)) {
			$criteria = $this->createObjectUpdateCriteria($first, $args);
			$objectName = get_class($first);
		}
		elseif (is_string($first)) {
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
		
		if ($count < 2)
			throw new \InvalidArgumentException();
		
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
	
	public function save($object)
	{
		$meta = $this->getMeta(get_class($object));
		$id = $this->mapper->getProperty($meta, $object, $meta->primary);
		
		if ($id)
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
		
		if (count($args) < 1)
			throw new \InvalidArgumentException();
		
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
	
	public function bindValues($stmt, $params)
	{
		foreach ($params as $k=>$v)
			$stmt->bindValue($k, $v);
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
