<?php

namespace Amiss\Type;

class Autoinc implements Handler
{
	public $type = 'INTEGER';
	
	function prepareValueForDb($value, $object, array $fieldInfo)
	{
		return $value;
	}
	
	function handleValueFromDb($value, $object, array $fieldInfo, $row)
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
