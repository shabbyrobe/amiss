<?php
namespace Amiss\Mapper;

class Arrays extends Base
{
    public $arrayMap;
    
    public function __construct($arrayMap=array())
    {
        parent::__construct();
        $this->arrayMap = $arrayMap;
    }

    public function canMap($id)
    {
        return isset($this->arrayMap[$id]);
    }

    protected function createMeta($id)
    {
        if (!isset($this->arrayMap[$id])) {
            throw new \InvalidArgumentException("Unknown id $id");
        }

        $info = $this->arrayMap[$id];
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
