<?php
namespace Amiss\Mongo\Type;

class Id implements \Amiss\Type\Handler
{
    function prepareValueForDb($value, array $fieldInfo)
    {
        return new \MongoId($value);
    }
    
    function handleValueFromDb($value, array $fieldInfo, $row)
    {
        return $value->__toString();
    }
    
    function createColumnType($engine, array $fieldInfo)
    {}
}
