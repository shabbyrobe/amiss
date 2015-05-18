<?php
namespace Amiss\Mapper;

/**
 * @package Mapper
 */
class Arrays extends Base
{
    public $arrayMap;
    
    public function __construct($arrayMap=array())
    {
        parent::__construct();
        
        $this->arrayMap = $arrayMap;
    }
    
    protected function createMeta($class)
    {
        if (!isset($this->arrayMap[$class])) {
            throw new \InvalidArgumentException("Unknown class $class");
        }

        $info = $this->arrayMap[$class];
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
        
        $meta = new \Amiss\Meta($class, $info, $parent);
        
        return $meta;
    }
}
