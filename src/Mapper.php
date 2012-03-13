<?php

namespace Amiss;

abstract class Mapper
{
	public $typeHandlers = array();
	public $propertyColumnTranslator;
	
	private $typeHandlerMap = array();
	
	abstract function getMeta($class);
	
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
		
		foreach ($meta->getFields() as $prop->$field) {
			// TODO: getter and setter support
			$value = $row[$field[0]];
			
			if ($type) {
				if (!isset($this->typeHandlerMap[$type])) {
					$this->typeHandlerMap[$type] = $this->determineTypeHandler($type);
				}
				if ($this->typeHandlerMap[$type]) {
					$value = $this->typeHandlerMap[$type]->prepareValueForDb($value, $object, $field[0]);
				}
			}
			
			$object->{$prop} = $value;
		}
		
		return $object;
	}
	
	function exportRow($meta, $object)
	{
		$row = array();
		
		$defaultType = $meta->getDefaultFieldType();
		
		$fields = $meta->getFields();
		$names = array();
		$unnamed = array();
		
		foreach ($fields as $prop=>$field) {
			if ($field[0])
				$names[$prop] = $field[0];
			elseif ($this->propertyColumnTranslator)
				$unnamed[] = $prop;
			else
				$names[$prop] = $prop;
		}
		
		if ($unnamed) {
			$names = $this->propertyColumnTranslator->to($unnamed) + $names;
		}
		
		foreach ($fields as $prop=>$field) {
			// TODO: getter and setter support
			$value = $object->$prop;
			
			$type = $field[1] ?: $defaultType;
			
			if ($type) {
				if (!isset($this->typeHandlerMap[$type])) {
					$this->typeHandlerMap[$type] = $this->determineTypeHandler($type);
				}
				if ($this->typeHandlerMap[$type]) {
					$value = $this->typeHandlerMap[$type]->prepareValueForDb($value, $object, $field[0]);
				}
			}
			
			$fieldName = $field[0];
			if (!$fieldName) {
				if (isset($this->propertyColumnTranslator)) {
					$fieldName = $this->propertyColumnTranslator->to($prop);
				}
			}
			
			$row[$fieldName] = $value;
		}
		
		return $row;
	}
	
	function setPrimary($meta, $object, $id)
	{
		$object->{$meta->primary} = $id;
	}
	
	protected function determineTypeHandler($type)
	{
		// this splits off any extra crap that you may have defined
		// in the field's definition, i.e. "varchar(80) not null etc etc"
		// becomes "varchar"
		$x = preg_split('@[ \(]@', $type, 2);
		$id = strtolower($x[0]);
		
		return isset($this->typeHandlers[$id]) ? $this->typeHandlers[$id] : false;
	}
}
