<?php

namespace Amiss;

abstract class Meta
{
	public $class;
	private $manager;
	protected $fields;
	protected $allFields;
	protected $parent;

	/**
	 * A reference between the field name and its type handler
	 */
	public $fieldHandlers=array();
	public $typeHandlers;
	
	protected $primary;
	protected $defaultFieldType;
	protected $relations;
	
	public function __construct($class, Meta $parent=null)
	{
		$this->class = $class;
		$this->parent = $parent;
	}
	
	abstract function getTable();
	
	function getManager()
	{
		if (!$this->manager && $this->parent) {
			$this->manager = $this->parent->getManager();
		}
		if (!$this->manager)
			throw new \UnexpectedValueException("Manager not set");
		
		return $this->manager;
	}

	function setManager($manager)
	{
		$this->manager = $manager;
	}

	public function getFields()
	{
		if ($this->allFields===null) {
			$fields = $this->fields;
			
			$current = $this;
			while ($current->parent) {
				$fields = array_merge($current->parent->getFields(), $fields);
				$current = $current->parent;
			}
			
			$this->allFields = $fields ?: array();
		}
		
		return $this->allFields;
	}

	function getField($field)
	{
		if (!$this->allFields)
			$this->getFields();
		
		if (isset($this->allFields[$field])) {
			return $this->allFields[$field];
		}
	}

	function setFields($fields)
	{
		$this->fields = $fields;
		$this->allFields = null;
	}

	function getRelations()
	{
		return $relations;
	}

	function setRelations($relations)
	{
		$this->relations = $relations;
	}

	function getDefaultFieldType()
	{
		if ($this->defaultFieldType===null && $this->parent) {
			$this->defaultFieldType = $this->parent->getDefaultFieldType();
		}
		return $this->defaultFieldType;
	}

	function getPrimary()
	{
		if (!$this->primary) {
			$pos = strrpos($class, '\\');
			$name = $pos ? substr($class, $pos+1) : $class;
			$this->primary = lcfirst($name).'Id';
		}
		
		return $this->primary;
	}
	
	function getTypeHandler($type)
	{
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
