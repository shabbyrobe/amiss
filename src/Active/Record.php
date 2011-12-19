<?php

namespace Amiss\Active;

use	Amiss\Connector,
	Amiss\RowExporter,
	Amiss\Exception;

abstract class Record implements RowExporter
{
	public static $relations=array();
	public static $fields=array();
	public static $primary;
	public static $table;
	public static $defaultFieldType=null;
	
	protected static $meta=array();
	
	/**
	 * Used only for testing
	 */
	public static function _reset()
	{
		self::$meta = array();
	}
	
	private $fetched = false;
	
	protected function beforeInsert()
	{}
	
	protected function beforeSave()
	{}
	
	protected function beforeUpdate()
	{}
	
	protected function beforeDelete()
	{}
	
	public function save()
	{
		$primary = static::getMeta()->getPrimary();
		if (!$primary)
			throw new Exception("Active record requires an autoincrement primary if you want to call 'save'");
		
		if (!$this->{$primary})
			$this->insert();
		else
			$this->update();
	}
	
	public function insert()
	{
		$meta = static::getMeta();
		$primary = $meta->getPrimary();
		
		if ($primary && $this->{$primary})
			throw new Exception("This record was already inserted");
		
		$this->beforeInsert();
		$this->beforeSave();
		$return = $meta->getManager()->insert($this);
		
		if ($primary) $this->{$primary} = $return;
	}
	
	public function update()
	{
		$args = func_get_args();
		$count = func_num_args();
		$meta = static::getMeta();
		$manager = $meta->getManager();
		
		$primary = null;
		if ($count == 0) {
			$primary = $meta->getPrimary();
			if (!$primary)
				throw new Exception("Active record requires an autoincrement primary if you want to call 'update' without a where clause");
		}
		
		$this->beforeSave();
		$this->beforeUpdate();
		if ($primary) {
			$manager->update($this, $primary);
		}
		else {
			$args = array_unshift($args, $this);
			call_user_func_array(array($manager, 'update'), $args);
		}
	}
	
	public function delete()
	{
		$args = func_get_args();
		$count = func_num_args();
		
		$meta = static::getMeta();
		$manager = $meta->getManager();
		
		$primary = null;
		if ($count == 0) {
			$primary = $meta->getPrimary();
			if (!$primary)
				throw new Exception("Active record requires an autoincrement primary if you want to call 'update' without a where clause");
		}
		
		$this->beforeDelete();
		if ($primary) {
			$manager->delete($this, $primary);
		}
		else {
			$args = array_unshift($args, $this);
			call_user_func_array(array($manager, 'delete'), $args);
		}
	}
	
	public function fetchRelated($name, $into=null)
	{
		$meta = static::getMeta();
		$relations = $meta->getRelations();
		
		if (!isset($relations[$name])) {
			throw new \InvalidArgumentException("Unknown relation $name");
		}
		$relation = $relations[$name];
		
		$for = $into ? array($this, $into) : $this;
		
		$method = null;
		if (isset($relation['one'])) {
			$method = 'getRelated';
			$type = $relation['one'];
		}
		elseif (isset($relation['many'])) {
			$method = 'getRelatedList';
			$type = $relation['many'];
		}
		else
			throw new \UnexpectedValueException("Expected 'one' or 'list' for relation, found $type");
		
		$manager = $meta->getManager();
		$type = $manager->resolveObjectName($type);
		
		$details = array($for, $type, $relation['on']);
		$related = call_user_func_array(array($manager, $method), $details);
		
		return $related;
	}
	
	/**
	 * Use ``static::getMeta()->getManager()`` instead.
	 * 
	 * @return Amiss\Manager
	 * @deprecated
	 */
	public static function getManager()
	{
		return static::getMeta()->getManager();
	}
	
	/**
	 * @return Amiss\Active\Meta
	 */
	public static function getMeta($class=null)
	{
		if (!$class)
			$class = get_called_class();
		
		if (!isset(self::$meta[$class])) {
			self::$meta[$class] = static::createMeta($class);
		}
		
		return self::$meta[$class];
	}
	
	protected static function createMeta($class)
	{
		$parent = get_parent_class($class);
		$meta = new Meta($class, $parent ? static::getMeta($parent) : null);
		return $meta;
	}
	
	public static function setManager($manager)
	{
		static::getMeta()->setManager($manager);
	}
	
	public static function addTypeHandler($handler, $types)
	{
		if (!is_array($types)) $types = array($types);
		
		foreach ($types as $type) {
			$type = strtolower($type);
			static::getMeta()->typeHandlers[$type] = $handler;
		}
	}
	
	public static function getByPk($key)
	{
		$primary = static::getMeta()->getPrimary();
		return static::get($primary.'=?', $key);
	}
	
	public static function __callStatic($name, $args)
	{
		$manager = static::getMeta()->getManager();
		
		$called = get_called_class();
		
		$exists = null;
		if ($name == 'get' || $name == 'getList' || $name == 'getRelated' || $name == 'getRelatedList' || $name == 'count') {
			$exists = true; 
			array_unshift($args, $called);
		}
		
		if ($exists === null)
			$exists = method_exists($manager, $name);
		 
		if ($exists)
			return call_user_func_array(array($manager, $name), $args);
		else
			throw new \BadMethodCallException("Unknown method $name");
	}

	public function exportRow()
	{
		$meta = static::getMeta();
		$fields = $meta->getFields();
		
		// if the active record's fields are defined, export the row here
		// otherwise we defer to the data mapper's default handling
		
		if ($fields) {
			$values = array();
			foreach ($fields as $k=>$v) {
				$field = null;
				$type = null;
				
				// if the key is a string, the value is the type.
				// if the key is numeric, the value is the field name and the type
				// is the default.
				// FIXME: this does not defer to the default properly.
				
				// quick method-less stringy test (strings always == zero). accurate enough.
				if ($k == 0 && $k !== 0) {
					$field = $k;
					$type = $v;
				}
				else {
					$field = $v;
					$value = $this->{$field};
					$type = $meta->getDefaultFieldType();
				}
				
				$value = $this->{$field};
				
				if ($type) {
					if (!isset($meta->fieldHandlers[$field])) {
						// set it to false if a type handler wasn't found so that 'isset' returns 
						// true (it wouldn't for 'null')
						$meta->fieldHandlers[$field] = $meta->getTypeHandler($type) ?: false;
					}
					
					$handler = $meta->fieldHandlers[$field];
					if ($handler) {
						$value = $handler->prepareValueForDb($value, $this, $field);
					}
				}
				
				$values[$field] = $value;
			}
			
			$primary = $meta->getPrimary();
			if ($primary && $this->$primary) {
				$values[$primary] = $this->$primary;
			}
			
			return $values;
		}
		else {
			return $meta->getManager()->getDefaultRowValues($this);
		}
	}
	
	public function afterFetch()
	{
		$this->fetched = true;
		
		$meta = static::getMeta();
		$fields = $meta->getFields();
		
		// handle type mappers 
		foreach ($fields as $k=>$v) {
			// quick method-less stringy test (strings always == zero). accurate enough.
			if ($k == 0 && $k !== 0) {
				if ($v) {
					$field = $k; $type = $v;
					
					if (!isset($meta->fieldHandlers[$field])) {
						// set it to false if a type handler wasn't found so that 'isset' returns 
						// true (it wouldn't for 'null')
						$meta->fieldHandlers[$field] = $meta->getTypeHandler($type) ?: false;
					}
					
					$handler = $meta->fieldHandlers[$field];
					if ($handler) {
						$this->$field = $handler->handleValueFromDb($this->$field, $this, $field);
					}
				}
			}
		}
	}
	
	public function __get($name)
	{
		$meta = static::getMeta();
		$fields = $meta->getFields();
		if ($this->fetched || ($fields && !isset($fields[$name]))) {
			throw new \BadMethodCallException("Unknown property $name");
		}
		else {
			return null;
		}
	}
	
	public static function getTableName()
	{
		/* 
		 * Don't update this method to return the static::$tableName value.
		 * Active\Meta has to do some gymnastics to register the table name
		 * with the manager. It'll have to be cleaned up at a later date.
		 */
	}
	
	public static function getRelations()
	{
		return static::$relations;
	}
	
	public static function getTypeHandlers()
	{
		return array();
	}
}
