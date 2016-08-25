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
    
    function getMeta($id, $strict=true)
    {
        if (!is_string($id)) {
            throw new \InvalidArgumentException();
        }
        if (isset($this->meta[$id])) {
            return $this->meta[$id];
        } elseif ($strict) {
            throw new \InvalidArgumentException();
        } else {
            return null;
        }
    }

    function canMap($id) { return isset($this->meta[$id]); }

    function createObject($meta, $row, $args=null) {}
    
    function populateObject($object, \stdClass $mapped, $meta=null) {}

    public function mapRowToProperties($meta, $input, $fieldMap=null) {}

    public function mapPropertiesToRow($meta, $input) {}

    function mapObjectToRow($object, $meta=null, $context=null) {}
    
    function determineTypeHandler($type) {}
}    

