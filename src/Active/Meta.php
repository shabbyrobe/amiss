<?php

namespace Amiss\Active;

use Amiss\Exception,
	Amiss\Manager
;

class Meta
{
	public $manager;
	public $class;
	public $table;
	public $parent;
	
	/**
	 * A reference between the field name and its type handler
	 */
	public $fieldHandlers=array();
	
	public $typeHandlers;
	
	public $primary;
	
	private $fields;
	private $defaultFieldType;
	private $registered;
	private $relations;
	
	public function __construct($class, Meta $parent=null)
	{
		$this->class = $class;
		$this->parent = $parent;
		
		if (!$this->table) {
			$this->table = forward_static_call(array($this->class, 'getTableName'))
				?: $class::$table
			;
		}
	}
	
	public function getManager()
	{
		if (!$this->manager && $this->parent) {
			$this->manager = $this->parent->getManager();
		}
		
		if (!$this->manager) {
			throw new Exception("Please assign a manager");
		}
		
		// Only register the table if the class has a parent.
		// This basically just excludes the base ActiveRecord type.
		if (!$this->registered && $this->parent) {
			if (!$this->table) 
				$this->table = $this->manager->getTableName($this->class);
			
			$this->manager->tableMap[$this->class] = $this->table;
			$this->registered = true;
		}
		return $this->manager;
	}
	
	public function setManager(Manager $manager)
	{
		$this->manager = $manager;
	}
	
	public function getRelations()
	{
		if (!$this->relations) {
			$class = $this->class;
			$this->relations = forward_static_call(array($class, 'getRelations'));
		}
		return $this->relations;
	}
	
	public function getFields()
	{
		if ($this->fields===null) {
			$class = $this->class;
			$fields = $class::$fields;
			
			$current = $this;
			while ($current->parent) {
				$fields = array_merge($current->parent->getFields(), $fields);
				$current = $current->parent;
			}
			
			$this->fields = $fields ?: array();
		}
		
		return $this->fields;
	}
	
	public function getDefaultFieldType()
	{
		if ($this->defaultFieldType===null) {
			$class = $this->class;
			$this->defaultFieldType = $class::$defaultFieldType;
			if (!$this->defaultFieldType && $this->parent) {
				$this->defaultFieldType = $this->parent->getDefaultFieldType();
			}
		}
		return $this->defaultFieldType;
	}
	
	public function getField($field)
	{
		if (!$this->fields)
			$this->getFields();
		
		if (isset($this->fields[$field])) {
			return $this->fields[$field];
		}
	}

	public function getPrimary()
	{
		if (!$this->primary) {
			$class = $this->class;
			/*
			$this->primary = forward_static_call(array($this->class, 'getPrimary'))
				?: $class::$primary
			;
			*/
			$this->primary = $class::$primary;
			
			if (!$this->primary) {
				$pos = strrpos($class, '\\');
				$name = $pos ? substr($class, $pos+1) : $class;
				$this->primary = lcfirst($name).'Id';
			}
		}
		
		return $this->primary;
	}
	
	public function getTypeHandler($type)
	{
		if ($this->typeHandlers === null) {
			$this->typeHandlers = forward_static_call(array($this->class, 'getTypeHandlers')) ?: array();
		}
		
		// this splits off any extra crap that you may have defined
		// in the field's definition
		$x = preg_split('@[ \(]@', $type, 2);
		$id = strtolower($x[0]);
		
		if (!isset($this->typeHandlers[$id]) && $this->parent) {
			// set it to false if a type handler wasn't found so that 'isset' returns 
			// true (it wouldn't for 'null')
			$this->typeHandlers[$id] = $this->parent->getTypeHandler($id) ?: false;
		}
		
		return isset($this->typeHandlers[$id]) ? $this->typeHandlers[$id] : null;
	}
}
