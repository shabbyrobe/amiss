<?php
namespace Amiss\Mapper;

class Arrays extends Base
{
    public $mappings;
    
    public function __construct($mappings=array())
    {
        parent::__construct();
        $this->mappings = $mappings;
    }

    public function canMap($id)
    {
        return isset($this->mappings[$id]);
    }

    protected function createMeta($id)
    {
        if (!isset($this->mappings[$id])) {
            throw new \InvalidArgumentException("Unknown id $id");
        }

        $info = $this->mappings[$id];
        $class = isset($info['class']) ? $info['class'] : $id;

        if (!isset($info['table'])) {
            $info['table'] = $this->getDefaultTable($class);
        }

        if (isset($info['fields'])) {
            $info['fields'] = $this->resolveUnnamedFields($info['fields']);
        }
        
        return new \Amiss\Meta($id, $info);
    }
}
