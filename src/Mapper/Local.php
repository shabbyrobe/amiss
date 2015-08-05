<?php
namespace Amiss\Mapper;

use Amiss\Meta;

class Local extends Base
{
    public $localName;
    
    public function __construct($localName='meta')
    {
        parent::__construct();
        $this->localName = $localName;
    }

    public function mapsClass($class)
    {
        return method_exists($class, $this->localName);
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

        if (!isset($info['table'])) {
            $info['table'] = $this->getDefaultTable($class);
        }

        if (isset($info['fields'])) {
            $info['fields'] = $this->resolveUnnamedFields($info['fields']);
        }
        
        return new \Amiss\Meta($class, $info);
    }
}
