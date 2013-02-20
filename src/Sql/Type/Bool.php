<?php
namespace Amiss\Sql\Type;

class Bool implements \Amiss\Type\Handler
{
    function handleValueFromDb($value, $object, array $fieldInfo, $row)
    {
    	return $value >= 1;
    }
    
    function prepareValueForDb($value, $object, array $fieldInfo)
    {
    	return $value == true;
    }

	function createColumnType($engine)
    {
        return 'tinyint(1)';
    }
}
