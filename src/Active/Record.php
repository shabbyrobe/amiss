<?php

namespace Amiss\Active;

use	Amiss\Connector,
	Amiss\Exception;

abstract class Record
{
	public static $relations=array();
	public static $fields=array();
	public static $primary;
	public static $table;
	public static $defaultFieldType=null;
	
	private $fetched = false;
	
	private static $managers=array();
	
	// for testing only
	public static function _reset()
	{
		self::$managers = array();
	}
	
	protected function beforeInsert()
	{}
	
	protected function beforeSave()
	{}
	
	protected function beforeUpdate()
	{}
	
	protected function beforeDelete()
	{}
	
	public function setFetched()
	{
		$this->fetched = true;
	}
	
	public function save()
	{
		$manager = static::getManager();
		$meta = $manager->getMeta(get_called_class());
		
		$primary = $meta->primary;
		if (!$primary)
			throw new Exception("Active record requires an autoincrement primary if you want to call 'save'");
		
		if (!$this->{$primary})
			$this->insert();
		else
			$this->update();
	}
	
	public function insert()
	{
		$manager = static::getManager();
		$meta = $manager->getMeta(get_called_class());
		
		$this->beforeInsert();
		$this->beforeSave();
		$return = $manager->insert($this);
	}
	
	public function update()
	{
		$args = func_get_args();
		$count = func_num_args();
		$manager = static::getManager();
		$meta = $manager->getMeta(get_called_class());
		
		$primary = null;
		if ($count == 0) {
			$primary = $meta->primary;
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
		
		$manager = static::getManager();
		$meta = $manager->getMeta(get_called_class());
		
		$primary = null;
		if ($count == 0) {
			$primary = $meta->primary;
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
		$manager = static::getManager();
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
		
		$details = array($for, $type, $relation['on']);
		$related = call_user_func_array(array($manager, $method), $details);
		
		return $related;
	}
	
	/**
	 * @return Amiss\Manager
	 */
	public static function getManager($class=null)
	{
		if (!$class)
			$class = get_called_class();
		
		if (!isset(self::$managers[$class])) {
			$parent = get_parent_class($class);
			if ($parent)
				self::$managers[$class] = static::getManager($parent);
		}
		
		if (!isset(self::$managers[$class]))
			throw new Exception("No manager defined against $class or any parent thereof");
		
		return self::$managers[$class];
	}
	
	public static function setManager($manager)
	{
		$class = get_called_class();
		self::$managers[$class] = $manager;
	}
	
	public static function updateTable()
	{
		$manager = static::getManager();
		$meta = $manager->getMeta(get_called_class());
		
		$args = func_get_args();
		array_unshift($args, $meta->class);
		
		return call_user_func_array(array($manager, 'update'), $args);
	}
	
	public static function getByPk($key)
	{
		$meta = static::getMeta();
		$primary = $meta->primary;
		return static::get($primary.'=?', $key);
	}
	
	public static function __callStatic($name, $args)
	{
		$manager = static::getManager();
		
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
	
	public static function getMeta()
	{
		return static::getManager()->getMeta(get_called_class());
	}
	
	public function __get($name)
	{
		$meta = static::getManager()->getMeta(get_class($this));
		
		$fields = $meta->getFields();
		if ($this->fetched || ($fields && !isset($fields[$name]))) {
			throw new \BadMethodCallException("Unknown property $name");
		}
		else {
			return null;
		}
	}
}
