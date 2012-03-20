<?php

namespace Amiss;

class Meta
{
	public $class;
	public $table;
	public $primary;
	public $relations;
	
	protected $fields;
	protected $allFields;
	protected $parent;
	protected $defaultFieldType;
	
	public function __construct($class, $table, array $info, Meta $parent=null)
	{
		$this->class = $class;
		$this->parent = $parent;
		$this->table = $table;
		$this->primary = isset($info['primary']) ? $info['primary'] : array();
		
		if ($this->primary && !is_array($this->primary))
			$this->primary = array($this->primary);
		
		$this->fields = isset($info['fields']) ? $info['fields'] : array();
		$this->relations = isset($info['relations']) ? $info['relations'] : array();
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
	
	function getDefaultFieldType()
	{
		if ($this->defaultFieldType===null && $this->parent) {
			$this->defaultFieldType = $this->parent->getDefaultFieldType();
		}
		return $this->defaultFieldType;
	}
	
	function getPrimaryValue($object)
	{
		$foundValue = false;
		
		$prival = array();
		foreach ($this->primary as $p) {
			$field = $this->getField($p);
			$value = !isset($field['getter']) ? $object->{$p} : call_user_func(array($object, $field['getter']));
			if ($value)
				$foundValue = true;
			
			$prival[$p] = $value;
		}
		
		if ($foundValue)
			return $prival;
	}
}
