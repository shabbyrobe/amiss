<?php
namespace Amiss\Type;

class Encoder implements Handler
{
    public $serialiser;
    public $deserialiser;
    public $innerHandler;

    /**
     * @var string|callable
     */
    public $columnType = 'TEXT';

    public function __construct($serialiser, $deserialiser, $innerHandler=null)
    {
        $this->serialiser = $serialiser;
        $this->deserialiser = $deserialiser;
        $this->innerHandler = $innerHandler;
    }

    function prepareValueForDb($value, array $fieldInfo)
    {
        $return = null;
        if ($value) {
            if ($this->innerHandler) {
                $value = $this->innerHandler->prepareValueForDb($value, $fieldInfo);
            }
            if ($value) {
                $return = call_user_func($this->serialiser, $value);
            }
        }
        return $return;
    }
    
    function handleValueFromDb($value, array $fieldInfo, $row)
    {
        $return = null;
        if ($value) {
            $return = call_user_func($this->deserialiser, $value);
            if ($this->innerHandler) {
                $return = $this->innerHandler->handleValueFromDb($return, $fieldInfo, $row);
            }
        }
        return $return;
    }
    
    function createColumnType($engine, array $fieldInfo)
    {
        if (is_string($this->columnType)) {
            return $this->columnType;
        } else {
            return call_user_func($this->columnType, $engine);
        }
    }
}
