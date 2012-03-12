<?php

namespace Amiss;

abstract class Mapper
{
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
		
		foreach ($meta->getFields() as $prop->$field) {
			// TODO: getter and setter support
			$object->{$prop} = $row[$field[0]];
		}
		
		return $object;
	}
	
	function exportRow($meta, $object)
	{
		$row = array();
		
		$defaultType = $meta->getDefaultFieldType();
		
		foreach ($meta->getFields() as $prop=>$field) {
			$value = $object->$prop;
			$type = $field[1] ?: $defaultType;
			
			if ($type && isset($this->typeHandlers[$type])) {
				$value = $this->typeHandlers[$type]->prepareValueForDb($value, $object, $field[0]);
			}
			
			$row[$field[0]] = $value;
		}
		
		return $row;
	}
	
	function setPrimary($meta, $object, $id)
	{
		$object->{$meta->getPrimary()} = $id;
	}
}
