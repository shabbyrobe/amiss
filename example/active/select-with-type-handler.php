<?php

use Amiss\Demo\Active\EventRecord;

class Handler implements \Amiss\Active\TypeHandler
{
	function prepareValueForDb($value)
	{
		if ($value instanceof \DateTime)
			$value = $value->format('Y-m-d H:i:s');
		
		return $value;
	}
	
	function handleValueFromDb($value)
	{
		$len = strlen($value);
		if ($value) {
			if ($len == 10) $value .= ' 00:00:00';
			$value = \DateTime::createFromFormat('Y-m-d H:i:s', $value);
		}
		return $value;
	}
	
	function createColumnType($engine)
	{}
}

\Amiss\Active\Record::addTypeHandler(new Handler, 'datetime'); 
$events = EventRecord::getList();
return $events;
