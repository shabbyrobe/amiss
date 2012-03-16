<?php

namespace Amiss\Mapper;

abstract class Base implements \Amiss\Mapper
{
	public $unnamedPropertyMapper;
	
	public $typeHandlers = array();
	
	public $objectNamespace;
	
	private $typeHandlerMap = array();
	
	abstract function getMeta($class);
	
	public function addTypeHandler($handler, $types)
	{
		if (!is_array($types)) $types = array($types);
		
		foreach ($types as $type) {
			$type = strtolower($type);
			$this->typeHandlers[$type] = $handler;
		}
	}
	
	/**
	 * Assumes that any name that contains a backslash is already resolved.
	 * This allows you to use fully qualified class names that are outside
	 * the mapped namespace.
	 */
	public function resolveObjectName($name)
	{
		return ($this->objectNamespace && strpos($name, '\\')===false ? $this->objectNamespace . '\\' : '').$name;
	}
	
	function createObject($meta, $row, $args)
	{
		if ($args) {
			$rc = new \ReflectionClass($meta->class);
			$object = $rc->newInstanceArgs($args);
		}
		else {
			$cname = $meta->class;
			$object = new $cname;
		}
		
		$defaultType = $meta->getDefaultFieldType();
		
		foreach ($meta->getFields() as $prop=>$field) {
			// TODO: getter and setter support
			$value = $row[$field['name']];
			
			$type = $field['type'] ?: $defaultType;
			
			if ($type) {
				if (!isset($this->typeHandlerMap[$type])) {
					$this->typeHandlerMap[$type] = $this->determineTypeHandler($type);
				}
				if ($this->typeHandlerMap[$type]) {
					$value = $this->typeHandlerMap[$type]->handleValueFromDb($value, $object, $field['name']);
				}
			}
			
			if (!isset($field['setter']))
				$object->{$prop} = $value;
			else
				call_user_func(array($object, $field['setter']), $value);
		}
		
		return $object;
	}
	
	function exportRow($meta, $object)
	{
		$row = array();
		
		$defaultType = $meta->getDefaultFieldType();
		
		foreach ($meta->getFields() as $prop=>$field) {
			if (!isset($field['getter']))
				$value = $object->$prop;
			else
				$value = call_user_func(array($object, $field['getter']));
			
			$type = $field['type'] ?: $defaultType;
			
			if ($type) {
				if (!isset($this->typeHandlerMap[$type])) {
					$this->typeHandlerMap[$type] = $this->determineTypeHandler($type);
				}
				if ($this->typeHandlerMap[$type]) {
					$value = $this->typeHandlerMap[$type]->prepareValueForDb($value, $object, $field['name']);
				}
			}
			
			$row[$field['name']] = $value;
		}
		
		return $row;
	}
	
	public function determineTypeHandler($type)
	{
		// this splits off any extra crap that you may have defined
		// in the field's definition, i.e. "varchar(80) not null etc etc"
		// becomes "varchar"
		$x = preg_split('@[ \(]@', $type, 2);
		$id = strtolower($x[0]);
		
		return isset($this->typeHandlers[$id]) ? $this->typeHandlers[$id] : false;
	}
	
	protected function getDefaultTable($class)
	{
		$table = $class;
		
		if ($pos = strrpos($table, '\\')) $table = substr($table, $pos+1);
		
		$table = trim(preg_replace_callback('/[A-Z]/', function($match) {
			return "_".strtolower($match[0]);
		}, str_replace('_', '', $table)), '_');

		return $table;
	}
	
	protected function resolveUnnamedFields($fields)
	{
		$unnamed = array();
		foreach ($fields as $prop=>$f) {
			if (!$f['name']) $unnamed[$prop] = $prop;
		}
		
		if ($unnamed) {
			if ($this->unnamedPropertyMapper)
				$unnamed = $this->unnamedPropertyMapper->to($unnamed);
			
			foreach ($unnamed as $name=>$field) {
				$fields[$name]['name'] = $field;
			}
		}
		
		return $fields;
	}
}
