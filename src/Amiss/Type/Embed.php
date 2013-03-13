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
        $class = $fieldInfo['type']['class'];
        $many = isset($fieldInfo['type']['many']) ? $fieldInfo['type']['many'] : false;

        $embedMeta = $this->mapper->getMeta($class);

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
        $class = $fieldInfo['type']['class'];
        $many = isset($fieldInfo['type']['many']) ? $fieldInfo['type']['many'] : false;
        
        $embedMeta = $this->mapper->getMeta($class);

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
    
    function createColumnType($engine)
    {}
}
