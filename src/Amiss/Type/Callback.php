<?php
namespace Amiss\Type;

class Callback implements Handler
{
	public $toDbCb;
	public $fromDbCb;
	public $columnTypeCb;
	
	public function __construct($toDbCb, $fromDbCb, $columnTypeCb=null) 
	{
		$this->toDbCb = $toDbCb;
		$this->fromDbCb = $fromDbCb;
		$this->columnTypeCb = $columnTypeCb;
	}
	
    function prepareValueForDb($value, $object, array $fieldInfo)
    {
    	if ($this->toDbCb)
    		return call_user_func_array($this->toDbCb, func_get_args());
    	else
    		return $value;
    }
    
    function handleValueFromDb($value, $object, array $fieldInfo, $row)
    {
    	if ($this->fromDbCb)
    		return call_user_func_array($this->fromDbCb, func_get_args());
    	else
    		return $value;
    }
    
    function createColumnType($engine)
    {
    	if ($this->columnType) {
    		return call_user_func($this->columnTypeCb, $engine);
    	}
    }
}
