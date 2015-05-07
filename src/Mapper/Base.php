<?php
namespace Amiss\Mapper;

use Amiss\Meta;

/**
 * @package Mapper
 */
abstract class Base extends \Amiss\Mapper
{
    public $unnamedPropertyTranslator;
    
    public $defaultTableNameTranslator;
    
    public $convertUnknownTableNames = true;
    
    public $typeHandlers = array();
    
    public $objectNamespace;

    public $skipNulls = false;
    
    private $typeHandlerMap = array();
    
    public function __construct()
    {}
    
    public function getMeta($class)
    {
        if (is_object($class)) {
            $class = get_class($class);
        }
        if (!is_string($class)) {
            throw new \InvalidArgumentException();
        }
        if (!isset($this->meta[$class])) {
            $resolved = $this->resolveObjectName($class);
            $this->meta[$class] = $this->createMeta($resolved);
        }
        return $this->meta[$class];
    }
    
    abstract protected function createMeta($class);

    public function addTypeHandler($handler, $types)
    {
        if (!is_array($types)) { $types = array($types); }
        
        foreach ($types as $type) {
            $type = strtolower($type);
            $this->typeHandlers[$type] = $handler;
        }
        return $this;
    }

    public function addTypeHandlers($handlers)
    {
        foreach ($handlers as $type=>$handler) {
            $this->typeHandlers[strtolower($type)] = $handler;
        }
        return $this;
    }

    public function fromObject($object, $meta=null, $context=null)
    {
        if (!$meta instanceof Meta) {
            $meta = $this->getMeta($meta ?: $object);
            if (!$meta) {
                throw new \InvalidArgumentException();
            }
        }

        $output = array();
        
        $defaultType = $meta->getDefaultFieldType();

        foreach ($meta->getFields() as $prop=>$field) {
            if (!isset($field['getter'])) {
                $value = $object->$prop;
            } else {
                $value = call_user_func(array($object, $field['getter']));
            }
            
            $type = $field['type'] ?: $defaultType;
            $typeId = $type['id'];
            
            if ($type) {
                if (!isset($this->typeHandlerMap[$typeId])) {
                    $this->typeHandlerMap[$typeId] = $this->determineTypeHandler($typeId);
                }
                if ($this->typeHandlerMap[$typeId]) {
                    $value = $this->typeHandlerMap[$typeId]->prepareValueForDb($value, $field);
                }
            }
            
            // don't allow array_merging. it breaks mongo compatibility and is pretty 
            // confused anyway.
            if (!$this->skipNulls || $value !== null) {
                $output[$field['name']] = $value;
            }
        }
        
        return $output;
    }

    public function determineTypeHandler($type)
    {
        // this splits off any extra crap that you may have defined
        // in the field's definition, i.e. "varchar(80) not null etc etc"
        // becomes "varchar"
        $x = preg_split('@[^A-Za-z0-9\-\_]@', trim($type), 2);
        $id = strtolower($x[0]);
        
        // must be false not null for isset tests
        $h = false;
        if (isset($this->typeHandlers[$id])) {
            $h = $this->typeHandlers[$id];
            if (is_callable($h)) {
                $h = $this->typeHandlers[$id] = call_user_func($h, $this);
            }
        }
        
        return $h;
    }
    
    /**
     * Assumes that any name that contains a backslash is already resolved.
     * This allows you to use fully qualified class names that are outside
     * the mapped namespace.
     */
    protected function resolveObjectName($name)
    {
        return ($this->objectNamespace && strpos($name, '\\')===false ? $this->objectNamespace . '\\' : '').$name;
    }
    
    protected function getDefaultTable($class)
    {
        $table = null;
        if ($this->defaultTableNameTranslator) {
            if ($this->defaultTableNameTranslator instanceof \Amiss\Name\Translator) {
                $table = $this->defaultTableNameTranslator->translate($class);
            } else {
                $table = call_user_func($this->defaultTableNameTranslator, $class);
            }
        }
        
        if ($table === null) {
            $table = $class;
            if ($this->convertUnknownTableNames) {
                $table = '`'.$this->convertUnknownTableName($class).'`';
            }
        }
        
        return $table;
    }
    
    public function convertUnknownTableName($class)
    {
        $table = $class;
        
        if ($pos = strrpos($table, '\\')) {
            $table = substr($table, $pos+1);
        }
        $table = trim(preg_replace_callback('/[A-Z]/', function($match) {
            return "_".strtolower($match[0]);
        }, str_replace('_', '', $table)), '_');
        
        return $table;
    }
    
    protected function resolveUnnamedFields($fields)
    {
        $unnamed = array();
        foreach ($fields as $prop=>$f) {
            if (!isset($f['name']) || !$f['name']) {
                $unnamed[$prop] = $prop;
            }
        }
        
        if ($unnamed) {
            if ($this->unnamedPropertyTranslator) {
                $unnamed = $this->unnamedPropertyTranslator->translate($unnamed);
            }
            foreach ($unnamed as $name=>$field) {
                $fields[$name]['name'] = $field;
            }
        }
        
        return $fields;
    }
}
