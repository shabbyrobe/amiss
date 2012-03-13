<?php

namespace Amiss;

abstract class Mapper
{
	public $typeHandlers = array();
	
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
		
		foreach ($meta->getFields() as $prop=>$field) {
			// TODO: getter and setter support
			$value = $row[$field[0]];
			
			$type = $field[1] ?: $defaultType;
			
			if ($type) {
				if (!isset($this->typeHandlerMap[$type])) {
					$this->typeHandlerMap[$type] = $this->determineTypeHandler($type);
				}
				if ($this->typeHandlerMap[$type]) {
					$value = $this->typeHandlerMap[$type]->handleValueFromDb($value, $object, $field[0]);
				}
			}
			
			if (!isset($field[2]))
				$object->{$prop} = $value;
			else
				call_user_func(array($object, $field[2][1]), $value);
		}
		
		return $object;
	}
	
	function exportRow($meta, $object)
	{
		$row = array();
		
		$defaultType = $meta->getDefaultFieldType();
		
		foreach ($meta->getFields() as $prop=>$field) {
			if (!isset($field[2]))
				$value = $object->$prop;
			else
				$value = call_user_func(array($object, $field[2][0]));
			
			$type = $field[1] ?: $defaultType;
			
			if ($type) {
				if (!isset($this->typeHandlerMap[$type])) {
					$this->typeHandlerMap[$type] = $this->determineTypeHandler($type);
				}
				if ($this->typeHandlerMap[$type]) {
					$value = $this->typeHandlerMap[$type]->prepareValueForDb($value, $object, $field[0]);
				}
			}
			
			$row[$field[0]] = $value;
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
