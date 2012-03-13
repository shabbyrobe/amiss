<?php

namespace Amiss\Type;

class Date implements Handler
{
	public $withTime;
	public $timeZone;
	
	public function __construct($withTime=true, $timeZone=null)
	{
		$this->withTime = $withTime;
		$this->timeZone = $timeZone;
	}
	
	function prepareValueForDb($value, $object, $fieldName)
	{
		$out = null;
		if ($value instanceof \DateTime) {
			$out = $value->format('Y-m-d'.($this->withTime ? ' H:i:s' : ''));
		}
		return $out;
	}
	
	function handleValueFromDb($value, $object, $fieldName)
	{
		$out = null;
		if ($value) {
			$format = 'Y-m-d'.($this->withTime ? ' H:i:s' : '');
			if ($this->timeZone)
				$out = \DateTime::createFromFormat($format, $value, $this->timeZone);
			else
				$out = \DateTime::createFromFormat($format, $value);
		}
		return $out;
	}
	
	function createColumnType($engine)
	{
		return $this->withTime ? 'datetime' : 'date';
	}
}
