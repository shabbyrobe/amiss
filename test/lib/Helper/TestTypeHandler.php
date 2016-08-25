<?php
namespace Amiss\Test\Helper;

class TestTypeHandler implements \Amiss\Type\Handler
{
    public $valueForDb;
    public $valueFromDb;
    public $columnType;
    
    public function __construct($data=array())
    {
        foreach ($data as $k=>$v) $this->$k = $v;
    }
    
    function prepareValueForDb($value, array $fieldInfo)
    {
        return $this->valueForDb;
    }
    
    function handleValueFromDb($value, array $fieldInfo, $row)
    {
        return $this->valueFromDb;
    }
    
    function createColumnType($engine, array $fieldInfo)
    {
        return $this->columnType;
    }
}
