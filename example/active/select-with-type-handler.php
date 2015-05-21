<?php

use Amiss\Demo\Active\EventRecord;

class Handler implements \Amiss\Type\Handler
{
    function prepareValueForDb($value, array $fieldInfo)
    {
        if ($value instanceof \DateTime)
            $value = $value->format('Y-m-d H:i:s');
        
        return $value;
    }
    
    function handleValueFromDb($value, array $fieldInfo, $row)
    {
        $len = strlen($value);
        if ($value) {
            if ($len == 10) $value .= ' 00:00:00';
            $value = \DateTime::createFromFormat('Y-m-d H:i:s', $value);
        }
        return $value;
    }
    
    function createColumnType($engine, array $fieldInfo)
    {}
}

\Amiss\Sql\ActiveRecord::getManager()->mapper->addTypeHandler(new Handler, 'datetime'); 
$events = EventRecord::getList();
return $events;
