<?php
namespace Amiss\Mapper;

use Amiss\Meta;
use Amiss\Exception;

/**
 * @package Mapper
 */
abstract class Base implements \Amiss\Mapper
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
        if (!isset($this->meta[$class])) {
            $resolved = $this->resolveObjectname($class);
            $this->meta[$class] = $this->createMeta($resolved);
        }
        return $this->meta[$class];
    }
    
    abstract protected function createMeta($class);

    public function addTypeHandler($handler, $types)
    {
        if (!is_array($types)) $types = array($types);
        
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

    public function fromObject($meta, $object, $context=null)
    {
        if (!$meta instanceof Meta) $meta = $this->getMeta($meta);

        $output = array();
        
        $defaultType = $meta->getDefaultFieldType();
        
        foreach ($meta->getFields() as $prop=>$field) {
            if (!isset($field['getter']))
                $value = $object->$prop;
            else
                $value = call_user_func(array($object, $field['getter']));

            if (is_object($value)) {
                $m = $this->getMeta(get_class($value));
                $primary = $m->getPrimaryValue($value);
                if (sizeof($primary) > 0 && isset($meta->relations[$field["name"]]["on"]) && isset($primary[$meta->relations[$field["name"]]["on"]])) {
                    $value = $primary[$meta->relations[$field["name"]]["on"]];
                } else {
                    $value = $value->__toString();
                }
            }
            
            $type = $field['type'] ?: $defaultType;
            $typeId = $type['id'];
            
            if ($type) {
                if (!isset($this->typeHandlerMap[$typeId])) {
                    $this->typeHandlerMap[$typeId] = $this->determineTypeHandler($typeId);
                }
                if ($this->typeHandlerMap[$typeId]) {
                    $value = $this->typeHandlerMap[$typeId]->prepareValueForDb($value, $object, $field);
                }
            }
            
            // don't allow array_merging. it breaks mongo compatibility and is pretty 
            // confused anyway.
            if (!$this->skipNulls || $value !== null) 
                $output[$field['name']] = $value;
        }
        
        return $output;
    }

    function fromObjects($meta, $input, $context=null)
    {
        if (!$meta instanceof Meta) $meta = $this->getMeta($meta);

        $out = array();
        if ($input) {
            foreach ($input as $key=>$item) {
                $out[$key] = $this->fromObject($meta, $item, $context);
            }
        }
        return $out;
    }
    
    function toObject($meta, $input, $args=null)
    {
        if (!$meta instanceof Meta) $meta = $this->getMeta($meta);

        $object = $this->createObject($meta, $input, $args);
        $this->populateObject($meta, $object, $input);
        return $object;
    }
    
    function toObjects($meta, $input, $args=null)
    {
        if (!$meta instanceof Meta) $meta = $this->getMeta($meta);

        $out = array();
        if ($input) {
            foreach ($input as $item) {
                $obj = $this->toObject($meta, $item);
                $out[] = $obj;
            }
        }
        return $out;
    }

    public function createObject($meta, $input, $args=null)
    {
        if (!$meta instanceof Meta) $meta = $this->getMeta($meta);

        $object = null;
        if ($meta->constructor == '__construct') {
            if ($args) {
                $rc = new \ReflectionClass($meta->class);
                $object = $rc->newInstanceArgs($args);
            }
            else {
                $cname = $meta->class;
                $object = new $cname;
            }
        }
        else {
            $rc = new \ReflectionClass($meta->class);
            $method = $rc->getMethod($meta->constructor);
            $object = $method->invokeArgs(null, $args ?: $input);
        }

        if (!$object instanceof $meta->class) {
            throw new \UnexpectedValueException(
                "Constructor {$meta->constructor} did not return instance of {$meta->class}"
            );
        }

        return $object;
    }
    
    public function populateObject($meta, $object, $input)
    {
        if (!$meta instanceof Meta) $meta = $this->getMeta($meta);

        $defaultType = null;
        
        $fields = $meta->getFields();
        $map = $meta->getColumnToPropertyMap();
        foreach ($input as $col=>$value) {
            if (!isset($map[$col]))
                continue; // throw exception?
            
            $prop = $map[$col];
            $field = $fields[$prop];
            $type = $field['type'];
            if (!$type) {
                if ($defaultType === null)
                    $defaultType = $meta->getDefaultFieldType() ?: false;
                
                $type = $defaultType;
            }
            
            if ($type) {
                $typeId = $type['id'];
                if (!isset($this->typeHandlerMap[$typeId])) {
                    $this->typeHandlerMap[$typeId] = $this->determineTypeHandler($typeId);
                }
                if ($this->typeHandlerMap[$typeId]) {
                    $value = $this->typeHandlerMap[$typeId]->handleValueFromDb($value, $object, $field, $input);
                }
            }
            
            if (!isset($field['setter']))
                $object->{$prop} = $value;
            else
                call_user_func(array($object, $field['setter']), $value);
        }
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
            if ($this->defaultTableNameTranslator instanceof \Amiss\Name\Translator) 
                $table = $this->defaultTableNameTranslator->translate($class);
            else
                $table = call_user_func($this->defaultTableNameTranslator, $class);
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
        
        if ($pos = strrpos($table, '\\')) $table = substr($table, $pos+1);
                
        $table = trim(preg_replace_callback('/[A-Z]/', function($match) {
            return "_".strtolower($match[0]);
        }, str_replace('_', '', $table)), '_');
        
        return $table;
    }
    
    protected function resolveUnnamedFields($fields)
    {
        $unnamed = array();
        foreach ($fields as $prop=>$f) {
            if (!isset($f['name']) || !$f['name']) $unnamed[$prop] = $prop;
        }
        
        if ($unnamed) {
            if ($this->unnamedPropertyTranslator)
                $unnamed = $this->unnamedPropertyTranslator->translate($unnamed);
            
            foreach ($unnamed as $name=>$field) {
                $fields[$name]['name'] = $field;
            }
        }
        
        return $fields;
    }
}
