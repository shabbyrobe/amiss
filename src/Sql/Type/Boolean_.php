<?php
namespace Amiss\Sql\Type;

class Boolean_ implements \Amiss\Type\Handler
{
    function handleValueFromDb($value, array $fieldInfo, $row)
    {
        return $value >= 1;
    }
    
    function prepareValueForDb($value, array $fieldInfo)
    {
        return $value == true;
    }

    function createColumnType($engine, array $fieldInfo)
    {
        return 'tinyint(1) unsigned';
    }
}
