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
	
	public $tableMap=array();
	
	/**
	 * Translator for object names to table names.
	 * 
	 * If an ``Amiss\NameMapper`` is used, only the ``to()`` method will be used.
	 * 
	 * @var mixed callable or Amiss\NameMapper
	 */
	public $objectToTableMapper=null;
	
	/**
	 * Translator for property names to column names.
	 * 
	 * Should implement ``to()`` to turn a property name into a column name,
	 * and ``from()`` to turn a column name into a property name 
	 * 
	 * @var Amiss\NameMapper
	 */
	public $propertyColumnMapper=null;
	
	public $convertUnderscores=true;
	
	public $objectNamespace=null;
	
	/**
	 * Whether or not Amiss should skip properties with a null value
	 * when converting an object to a row.
	 * 
	 * @var bool
	 */
	public $dontSkipNulls=false;
	
	public $queries = 0;
	
	public function __construct($connector)
	{
		if (is_array($connector)) 
			$connector = Connector::create($connector);
		
		$this->connector = $connector;
	}
	
	/**
	 * @return \PDO
	 */
	public function getConnector()
	{
		return $this->connector;
	}
	
	public function get($object)
	{
		$criteria = $this->createSelectCriteria(array_slice(func_get_args(), 1));
		$table = $this->getTableName($object);
		
		list ($limit, $offset) = $criteria->getLimitOffset();
		
		if ($limit && $limit != 1)
			throw new Exception("Limit must be one or zero");
		
		list ($query, $params) = $criteria->buildQuery($table);
		$stmt = $this->getConnector()->prepare($query);
		$this->execute($stmt, $params);
		
		$obj = null;
		while ($row = $this->fetchObject($stmt, $object, $criteria->args)) {
			if ($obj)
				throw new Exception("Query returned more than one row");
			$obj = $row;
		}
		return $obj;
	}

	public function getList($object)
	{
		$criteria = $this->createSelectCriteria(array_slice(func_get_args(), 1));
		$table = $this->getTableName($object);
		
		list ($query, $params) = $criteria->buildQuery($table);
		
		$stmt = $this->getConnector()->prepare($query);
		$this->execute($stmt, $params);
		
		$objects = array();
		while ($row = $this->fetchObject($stmt, $object, $criteria->args)) {
			$objects[] = $row;
		}
		return $objects;
	}

	public function count($object, $criteria=null)
	{
		$criteria = $this->createSelectCriteria(array_slice(func_get_args(), 1));
		$table = $this->getTableName($object);
		
		list ($where, $params) = $criteria->buildClause();
		
		$fields = $criteria->buildFields();
		$query = "SELECT COUNT($fields) FROM $table "
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
		
		if ($count == 1) {
			$table = $this->getTableName(get_class($args[0]));
			$values = $this->exportRow($args[0]);
		}
		elseif ($count == 2) {
			$table = $this->getTableName($args[0]);
			$values = $args[1];
		}
		
		$columns = array();
		$count = count($values);
		foreach ($values as $k=>$v) {
			if ($k[0]==':') {
				$k = substr($k, 1);
			}
			$columns[] = '`'.str_replace('`', '', $k).'`';
		}
		$sql = "INSERT INTO $table(".implode(',', $columns).") VALUES(?".($count > 1 ? str_repeat(",?", $count-1) : '').")";
		
		$stmt = $this->getConnector()->prepare($sql);
		++$this->queries;
		$stmt->execute(array_values($values));
		return $this->getConnector()->lastInsertId();
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
	
	public function save($object, $autoIncrementId)
	{
		if (!$object->$autoIncrementId) {
			$id = $this->insert($object);
			$object->$autoIncrementId = $id;
		}
		else {
			$this->update($object, $autoIncrementId);
			$id = $object->$autoIncrementId;
		}
		return $id;
	}
	
	public function resolveObjectName($name)
	{
		return ($this->objectNamespace && strpos($name, '\\')===false ? $this->objectNamespace . '\\' : '').$name;
	}
	
	public function fetchObject($stmt, $name, $args=null)
	{
		$fqcn = $this->resolveObjectName($name);
		
		$assoc = $stmt->fetch(\PDO::FETCH_ASSOC);
		if (!$assoc) return false;
		
		$class = new $fqcn;
		
		if ($class instanceof RowBuilder) {
			$class->buildObject($assoc);
		}
		else {
			$names = null;
			if (isset($this->propertyColumnMapper)) {
				$names = $this->propertyColumnMapper->from(array_keys($assoc));
			}
			foreach ($assoc as $k=>$v) {
				if ($names && isset($names[$k])) {
					$prop = $names[$k];
				}
				else {
					if ($this->convertUnderscores) {
						$prop = trim(preg_replace_callback('/_(.)/', function($match) {
							return strtoupper($match[1]);
						}, $k), '_');
					}
					else {
						$prop = $k;
					}
				}
				$class->$prop = $v;
			}
		}
		
		if (method_exists($class, 'afterFetch')) {
			$class->afterFetch($this);
		}
		
		return $class;
	}
	
	protected function exportRow($obj)
	{
		if ($obj instanceof RowExporter) {
			$values = $obj->exportRow();
			if (!is_array($values)) {
				throw new Exception("Row exporter must return an array!");
			}
		}
		else {
			$values = $this->getDefaultRowValues($obj);
		}
		
		return $values;
	}
	
	public function getDefaultRowValues($obj)
	{
		$values = array();
		
		$data = (array)$obj;
		$names = null;
		if ($this->propertyColumnMapper)
			$names = $this->propertyColumnMapper->to(array_keys($data));
		
		foreach ($obj as $k=>$v) {
			if ($names && isset($names[$k])) {
				$k = $names[$k];
			}
			if (!is_array($v) && !is_object($v) && !is_resource($v) && ($this->dontSkipNulls || $v !== null)) {
				$values[$k] = $v;
			}
		}
		return $values;
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
		$uc = new Criteria\Update();	
		$uc->set = $this->exportRow($object);
		
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
		$table = $this->getTableName($objectName);
		
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
		$table = $this->getTableName($objectName);
		
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
	
	/**
	 * Returns a quoted table name for a class name
	 */
	public function getTableName($class)
	{
		$class = ltrim($class, '\\');
		
		if (isset($this->tableMap[$class])) {
			$table = $this->tableMap[$class];
		}
		else {
			if (isset($this->objectToTableMapper)) {
				if ($this->objectToTableMapper instanceof Name\Mapper) {
					$result = $this->objectToTableMapper->to(array($class));
					$table = current($result);
				}
				else {
					$table = call_user_func($this->objectToTableMapper, $class);
				}
			}
			else {
				if ($pos = strrpos($class, '\\')) $class = substr($class, $pos+1);
				$table = trim(preg_replace_callback('/[A-Z]/', function($match) {
					return "_".strtolower($match[0]);
				}, $class), '_');
			}
		}
		
		$table = '`'.str_replace('`', '', $table).'`';
		return $table;
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
			throw new \InvalidArgumentException('Couldn\'t parse arguments');
		}
	}
}
