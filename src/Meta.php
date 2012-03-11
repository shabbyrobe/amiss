<?php

namespace Amiss;

class Meta
{
	public $class;
	public $table;
	
	protected $fields;
	protected $allFields;
	protected $parent;
	protected $primary;
	protected $defaultFieldType;
	protected $relations;

	public function __construct($class, $table, array $info, Meta $parent=null)
	{
		$this->class = $class;
		$this->parent = $parent;
		$this->table = $table;
		$this->primary = isset($info['primary']) ? $info['primary'] : null;
		$this->fields = isset($info['fields']) ? $info['fields'] : null;
		$this->relations = isset($info['relations']) ? $info['relations'] : null;
		$this->defaultFieldType = isset($info['defaultFieldType']) ? $info['defaultFieldType'] : null;
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
	
	function getRelations()
	{
		return $this->relations;
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
