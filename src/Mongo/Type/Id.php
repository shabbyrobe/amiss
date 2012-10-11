<?php
namespace Amiss\Mongo\Type;

class Id implements \Amiss\Type\Handler
{
    function prepareValueForDb($value, $object, array $fieldInfo)
    {
        return new \MongoId($value);
    }
    
    function handleValueFromDb($value, $object, array $fieldInfo, $row)
    {
        return $value->__toString();
    }
    
    function createColumnType($engine)
    {}
}
