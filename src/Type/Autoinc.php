<?php

namespace Amiss\Type;

class Autoinc implements Handler
{
	public $type = 'INTEGER';
	
	function prepareValueForDb($value, $object, $fieldName)
	{
		return $value;
	}
	
	function handleValueFromDb($value, $object, $fieldName)
	{
		return (int)$value;
	}
	
	function createColumnType($engine)
	{
		if ($engine == 'sqlite')
			return "INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT";
		else
			return $this->type." NOT NULL PRIMARY KEY AUTO_INCREMENT";
	}	
}
