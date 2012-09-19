<?php

namespace Amiss\Type;

class Embed implements Handler
{
    /**
     * @var Amiss\Mapper
     */
    public $mapper;

    /**
     * @var bool
     */
    public $many;

    public function __construct($mapper, $many=null)
    {
        if ($many === null) $many = false;
        $this->many = $many;
        $this->mapper = $mapper;
    }

    function prepareValueForDb($value, $object, array $fieldInfo)
    {
        $fieldType = trim($fieldInfo['type']);
        if (!isset($this->typeCache[$fieldType])) {
            $this->typeCache[$fieldType] = $this->extractClass($fieldType);
        }
        $type = $this->typeCache[$fieldType];

        $embedMeta = $this->mapper->getMeta($type);

        $return = null;
        if ($this->many) {
            $return = array();
            if ($value) {
                foreach ($value as $key=>$item) {
                    $return[$key] = $this->mapper->exportRow($embedMeta, $item);
                }
            }
        }
        else {
            $return = $value ? $this->mapper->exportRow($embedMeta, $value) : null;
        }
        return $return;
    }
    
    function handleValueFromDb($value, $object, array $fieldInfo, $row)
    {
        $fieldType = trim($fieldInfo['type']);
        if (!isset($this->typeCache[$fieldType])) {
            $this->typeCache[$fieldType] = $this->extractClass($fieldType);
        }
        $type = $this->typeCache[$fieldType];
        
        $embedMeta = $this->mapper->getMeta($type);

        $return = null;

        if ($this->many) {
            $return = array();
            if ($value) {
                foreach ($value as $key=>$item) {
                    $obj = $this->mapper->createObject($embedMeta, $value);
                    $this->mapper->populateObject($embedMeta, $obj, $item);
                    $return[$key] = $obj;
                }
            }
        }
        else {
            $return = $this->mapper->createObject($embedMeta, $value);
            $this->mapper->populateObject($embedMeta, $return, $value);
        }
        return $return;
    }

    private function extractClass($type)
    {
        $split = explode(' ', $type, 2);
        if (!isset($split[1]))
            throw new \Exception('misconfigured type - must specify class name after type name');
        
        return trim($split[1]);
    }
    
    function createColumnType($engine)
    {}
}
