<?php
namespace Amiss\Mapper;

use Amiss\Meta;

/**
 * @package Mapper
 */
class Local extends Base
{
    public $localName;
    
    public function __construct($localName='meta')
    {
        parent::__construct();
        $this->localName = $localName;
    }
    
    protected function createMeta($id)
    {
        $class = $id;
        $fn = $this->localName;
        if (!method_exists($class, $fn)) {
            throw new \UnexpectedValueException("Static function $fn not found on $class");
        }
        $info = $class::$fn();
        if ($info instanceof Meta) {
            return $info;
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
