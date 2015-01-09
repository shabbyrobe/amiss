<?php
namespace Amiss\Type;

interface Handler
{
    function prepareValueForDb($value, array $fieldInfo);
    
    /**
     * Handle a value retrieved from the database.
     * 
     * This value should be returned after you transform it.
     * 
     * Population of the handled value happens outside the type handler.
     * 
     * @param mixed $value The value retrieved from the database
     * @param array $fieldInfo The field's metadata.
     * @param array $row The row, exactly as retrieved from the database.
     */
    function handleValueFromDb($value, array $fieldInfo, $row);
    
    /**
     * It's ok to return nothing from this - the default column type
     * will be used.
     */
    function createColumnType($engine);
}
