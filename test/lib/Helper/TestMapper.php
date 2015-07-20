<?php
namespace Amiss\Test\Helper;

class TestMapper implements \Amiss\Mapper
{
    use \Amiss\MapperTrait;

    public $meta;
    
    function __construct($meta=array())
    {
        $this->meta = $meta;
    }
    
    function getMeta($class)
    {
        if (is_object($class)) {
            $class = get_class($class);
        }
        return isset($this->meta[$class]) ? $this->meta[$class] : null;
    }

    function createObject($meta, $row, $args=null) {}
    
    function populateObject($object, \stdClass $mapped, $meta=null) {}

    public function mapRowToProperties($input, $meta=null, $fieldMap=null) {}

    public function mapPropertiesToRow($input, $meta=null) {}

    function mapObjectToRow($object, $meta=null, $context=null) {}
    
    function determineTypeHandler($type) {}
}    

