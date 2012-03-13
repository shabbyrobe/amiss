<?php

namespace Amiss;

class Meta
{
	public $class;
	public $table;
	public $primary;
	
	protected $fields;
	protected $allFields;
	protected $parent;
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
	
	/**
	 * Get a list of fields for this class
	 * 
	 * The field list is a hash of 2-tuples keyed by property name.
	 * The first 2-tuple element contains either an explicit field
	 * name that the property maps to, or boolean "false" if the field
	 * name should be inferred.
	 * The second element contains the field's "type", for the purpose
	 * of looking up a type handler. This may be false if the type handler
	 * should be either inferred or ignored. 
	 */
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
}
