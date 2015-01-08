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

    function prepareValueForDb($value, array $fieldInfo)
    {
        $class = $fieldInfo['type']['class'];
        $many = isset($fieldInfo['type']['many']) ? $fieldInfo['type']['many'] : false;

        $embedMeta = $this->mapper->getMeta($class);

        $return = null;
        if ($many) {
            $return = array();
            if ($value) {
                foreach ($value as $key=>$item) {
                    $return[$key] = $this->mapper->fromObject($item, $embedMeta);
                }
            }
        }
        else {
            $return = $value ? $this->mapper->fromObject($value, $embedMeta) : null;
        }
        return $return;
    }
    
    function handleValueFromDb($value, array $fieldInfo, $row)
    {
        $class = $fieldInfo['type']['class'];
        $many = isset($fieldInfo['type']['many']) ? $fieldInfo['type']['many'] : false;
        
        $embedMeta = $this->mapper->getMeta($class);

        $return = null;

        if ($many) {
            $return = array();
            if ($value) {
                foreach ($value as $key=>$item) {
                    $obj = $this->mapper->toObject($item, null, $embedMeta);
                    $return[$key] = $obj;
                }
            }
        }
        else {
            $return = $this->mapper->toObject($value, null, $embedMeta);
        }
        return $return;
    }
    
    function createColumnType($engine)
    {}
}
