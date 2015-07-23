<?php
namespace Amiss\Mapper;

use Amiss\Meta;

/**
 * @package Mapper
 */
abstract class Base implements \Amiss\Mapper
{
    use \Amiss\MapperTrait;

    public $unnamedPropertyTranslator;
    
    public $defaultTableNameTranslator;
    
    public $convertUnknownTableNames = true;
    
    public $typeHandlers = array();
    
    /**
     * @deprecated Don't use any more, use this pattern instead:
     *     use My\Name\Space;
     *     $mapper->getMeta(Space::class);
     * It will be removed as soon as I work out a neater way to do the Test\Factory without it.
     */
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
        
        $meta = null;
        if (!isset($this->meta[$class])) {
            if ($this->objectNamespace) {
                $resolved = $this->resolveObjectName($class);
                $meta = $this->meta[$class] = $this->createMeta($resolved);
                if ($resolved != $class) {
                    $this->meta[$resolved] = $meta;
                }
            }
            else {
                $meta = $this->meta[$class] = $this->createMeta($class);
            }
        }
        return $meta ?: $this->meta[$class];
    }
    
    abstract protected function createMeta($class);

    public function mapPropertiesToRow($input, $meta=null)
    {
        if (!$meta instanceof Meta) {
            $meta = $this->getMeta($meta ?: $input);
            if (!$meta) { throw new \InvalidArgumentException(); }
        }

        $defaultType = null;
        $properties = $meta->getProperties();
        $fields = [];
        foreach ($input as $propId=>$value) {
            if (!is_string($propId)) {
                continue;
            }
            if (!isset($properties[$propId])) {
                throw new \UnexpectedValueException("Unknown property '$propId' for meta {$meta->class}");
            }
            $property = $properties[$propId];
            $type = isset($property['type']) ? $property['type'] : null;
            if (!$type) {
                if ($defaultType === null) {
                    $defaultType = $meta->getDefaultFieldType() ?: false;
                }
                $type = $defaultType;
            }
            
            if ($type) {
                $typeId = $type['id'];
                if (!isset($this->typeHandlerMap[$typeId])) {
                    $this->typeHandlerMap[$typeId] = $this->determineTypeHandler($typeId);
                }
                if ($this->typeHandlerMap[$typeId]) {
                    $value = $this->typeHandlerMap[$typeId]->prepareValueForDb($value, $property, $input);
                }
            }
            $fields[$property['name']] = $value;
        }

        return $fields;
    }

    public function mapRowToProperties($input, $meta=null, $fieldMap=null)
    {
        if (!$meta instanceof Meta) {
            $meta = $this->getMeta($meta ?: $input);
            if (!$meta) { throw new \InvalidArgumentException(); }
        }

        if (!$fieldMap) { $fieldMap = $meta->getColumnToPropertyMap(); }

        $mapped = [];
        $properties = $meta->getProperties();
        $defaultType = null;

        foreach ($input as $col=>$value) {
            $propId = isset($fieldMap[$col]) ? $fieldMap[$col] : $col;
            if (!isset($properties[$propId])) {
                continue;
            }

            $property = $properties[$propId];
            $type = isset($property['type']) ? $property['type'] : null;
            if (!$type) {
                if ($defaultType === null) {
                    $defaultType = $meta->getDefaultFieldType() ?: false;
                }
                $type = $defaultType;
            }
            
            if ($type) {
                $typeId = $type['id'];
                if (!isset($this->typeHandlerMap[$typeId])) {
                    $this->typeHandlerMap[$typeId] = $this->determineTypeHandler($typeId);
                }
                if ($this->typeHandlerMap[$typeId]) {
                    try {
                        $value = $this->typeHandlerMap[$typeId]->handleValueFromDb($value, $property, $input);
                    }
                    catch (\Exception $ex) {
                        throw new \RuntimeException("An exception occurred while attempting to read {$meta->class}->{$propId} from the database", null, $ex);
                    }
                }
            }

            if (isset($mapped[$propId])) {
                throw new \UnexpectedValueException();
            }
            $mapped[$propId] = $value;
        }

        return (object) $mapped;
    }

    public function formatParams(Meta $meta, $propertyParamMap, $params)
    {
        $fields = $meta->getFields();
        $defaultType = $meta->getDefaultFieldType();

        foreach ($propertyParamMap as $prop=>$propParams) {
            if (!isset($fields[$prop])) {
                throw new \UnexpectedValueException("Field $prop does not exist for class {$meta->class}");
            }
            if (!is_array($propParams)) {
                $propParams = [$propParams];
            }
            $field = $fields[$prop];

            $type = $field['type'] ?: $defaultType;
            $typeId = $type['id'];
            
            if ($type) {
                if (!isset($this->typeHandlerMap[$typeId])) {
                    $this->typeHandlerMap[$typeId] = $this->determineTypeHandler($typeId);
                }
                if ($this->typeHandlerMap[$typeId]) {
                    foreach ($propParams as $propParam) {
                        $params[$propParam] = $this->typeHandlerMap[$typeId]->prepareValueForDb($params[$propParam], $field);
                    }
                }
            }
        }
        return $params;
    }

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
        $this->typeHandlers = array_change_key_case($handlers);
        return $this;
    }

    public function mapObjectToRow($object, $meta=null, $context=null)
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
        $base = ($this->objectNamespace && strpos($name, '\\') === false 
            ? $this->objectNamespace . '\\' 
            : '');
        return $base.$name;
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
                $table = $this->convertUnknownTableName($class);
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
            // if it's not a string and the 'name' is unset or empty
            if (!($f == 0 && $f != "0") && (!isset($f['name']) || !$f['name'])) {
                $unnamed[$prop] = $prop;
            }
        }
        
        if ($unnamed) {
            if ($this->unnamedPropertyTranslator) {
                $unnamed = $this->unnamedPropertyTranslator->translate($unnamed);
            }
            foreach ($unnamed as $name=>$field) {
                if ($fields[$name] === true) {
                    $fields[$name] = ['name'=>$field];
                } else {
                    $fields[$name]['name'] = $field;
                }
            }
        }
        
        return $fields;
    }
}
