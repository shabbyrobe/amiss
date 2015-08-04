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

    public function mapsClass($class)
    {
        return isset($this->arrayMap[$class]);
    }

    protected function createMeta($class)
    {
        if (!isset($this->arrayMap[$class])) {
            throw new \InvalidArgumentException("Unknown class $class");
        }

        $info = $this->arrayMap[$class];

        class_name: {
            $class = isset($info['class']) ? $info['class'] : $class;
            unset($info['class']);
        }
        
        $parent = null;
        parent_class: {
            if (isset($info['inherit']) && $info['inherit']) {
                $parent = null;
                $parentClass = get_parent_class($class);
                if ($parentClass) {
                    $parent = $this->getMeta($parentClass);
                }
                unset($info['inherit']);
            }
        }

        if (!isset($info['table'])) {
            $info['table'] = $this->getDefaultTable($class);
        }

        if (isset($info['fields'])) {
            $info['fields'] = $this->resolveUnnamedFields($info['fields']);
        }
        
        return new \Amiss\Meta($class, $info, $parent);
    }
}
