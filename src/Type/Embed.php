<?php

namespace Amiss\Type;

class Embed implements Handler
{
    /**
     * @var Amiss\Mapper
     */
    public $mapper;

    public function __construct($mapper)
    {
        $this->mapper = $mapper;
    }

    function prepareValueForDb($value, $object, array $fieldInfo)
    {
        $fieldType = trim($fieldInfo['type']);
        if (!isset($this->typeCache[$fieldType])) {
            $this->typeCache[$fieldType] = $this->extractClass($fieldType);
        }
        list($type, $many) = $this->typeCache[$fieldType];

        $embedMeta = $this->mapper->getMeta($type);

        $return = null;
        if ($many) {
            $return = array();
            if ($value) {
                foreach ($value as $key=>$item) {
                    $return[$key] = $this->mapper->fromObject($embedMeta, $item);
                }
            }
        }
        else {
            $return = $value ? $this->mapper->fromObject($embedMeta, $value) : null;
        }
        return $return;
    }
    
    function handleValueFromDb($value, $object, array $fieldInfo, $row)
    {
        $fieldType = trim($fieldInfo['type']);
        if (!isset($this->typeCache[$fieldType])) {
            $this->typeCache[$fieldType] = $this->extractClass($fieldType);
        }
        list($type, $many) = $this->typeCache[$fieldType];
        
        $embedMeta = $this->mapper->getMeta($type);

        $return = null;

        if ($many) {
            $return = array();
            if ($value) {
                foreach ($value as $key=>$item) {
                    $obj = $this->mapper->toObject($embedMeta, $item);
                    $return[$key] = $obj;
                }
            }
        }
        else {
            $return = $this->mapper->toObject($embedMeta, $value);
        }
        return $return;
    }

    private function extractClass($type)
    {
        $split = explode(' ', $type, 2);
        if (!isset($split[1]))
            throw new \Exception('misconfigured type - must specify class name after type name');
        
        $class = trim($split[1]);
        $many = false;
        if (preg_match('/^(.*)\[\]$/', $class, $match)) {
            $class = $match[1];
            $many = true;
        }

        return [$class, $many];
    }
    
    function createColumnType($engine)
    {}
}
