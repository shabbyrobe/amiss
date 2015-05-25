<?php
namespace Amiss\Mapper;

/**
 * @package Mapper
 */
class Arrays extends Base
{
    public $arrayMap;
    
    /**
     * The array map may not have object namespaces prepended to the
     * keys. $this->objectNamespace may be set after construction by
     * existing class users.
     * 
     * Cheapo hack, solve the real problem another day.
     */
    private $arrayMapResolved = false;

    public function __construct($arrayMap=array())
    {
        parent::__construct();
        $this->arrayMap = $arrayMap;
    }
    
    protected function createMeta($class)
    {
        resolve_map: if (!$this->arrayMapResolved) {
            $resolvedMap = [];
            foreach ($this->arrayMap as $k=>$m) {
                $k = ($this->objectNamespace ? $this->objectNamespace.'\\' : '').$k;
                $resolvedMap[$k] = $m;
            }
            $this->arrayMap = $resolvedMap;
            $this->arrayMapResolved = true;
        }

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
