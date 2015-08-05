<?php
namespace Amiss;

use Amiss\Exception;

class Meta
{
    public $class;
    public $table;
    public $schema;
    public $primary;
    public $constructor;
    public $constructorArgs = [];
    public $autoinc;
    
    // queries that use this Meta which do not explicitly pass 'null' as their
    // order will use this.
    public $defaultOrder = [];

    /**
     * Array of relation arrays, hashed by property name
     * 
     * Relation arrays *must* contain at least a type at index 0:
     *   $rel = ['one'];
     * 
     * The meta also looks for the following keys:
     * - id   (will be assigned by this class)
     * - mode (default, auto, class)
     * 
     * All other values in the array are ignored by the meta and passed 
     * directly to the relator.
     * 
     * Example:
     *   $meta->relations = array(
     *     // the 'of' and 'on' keys are required by Amiss\Sql\Relator\OneMany
     *     'foo'=>array('one', 'of'=>'Artist', 'on'=>'artistId'),
     *     
     *     // the blahblah relator has different ideas
     *     'bar'=>array('blahblah', 'fee'=>'fi', 'fo'=>'fum'),
     *   );
     */
    public $relations = [];

    /**
     * Additional metadata found but not explicitly handled by the mapper
     */
    public $ext;

    public $autoRelations = [];

    public $indexes = [];

    public $canInsert = true;
    public $canUpdate = true;
    public $canDelete = true;

    public $on = [];

    /**
     * Array of fields, hashed by property name
     */
    public $fields = [];

    public $columnMap;

    protected $properties;
    
    /** @var array|null */
    public $fieldType;

    function __sleep()
    {
        // precache this stuff before serialization
        $this->getProperties();

        return array(
            'class', 'table', 'schema', 'primary', 'relations', 'fields',
            'fieldType', 'columnMap', 'autoRelations',
            'indexes', 'constructor', 'constructorArgs', 'ext', 'properties',
            'canInsert', 'canUpdate', 'canDelete', 'defaultOrder', 'on', 'autoinc',
        ); 
    }

    public function __construct($class, array $info)
    {
        $this->class  = ltrim($class, "\\");
        $this->table  = isset($info['table'])   ? $info['table']   : null;
        $this->schema = isset($info['schema'])  ? $info['schema']  : null;

        $this->defaultOrder = isset($info['defaultOrder']) ? $info['defaultOrder'] : null;

        primary: {
            if (isset($info['primary'])) {
                $this->primary = (array)$info['primary'];
            } else {
                $this->primary = [];
            }
        }

        if (isset($info['indexes'])) {
            $this->setIndexes($info['indexes']);
        }

        if (isset($info['fields'])) {
            // set indexes first: setFields is reliant on them 
            $this->setFields($info['fields']);
        }

        if ($this->primary) {
            // must happen after setFields - setFields can influence the primary
            $this->indexes['primary'] = ['fields'=>$this->primary, 'key'=>true];
        }

        if (isset($info['relations'])) {
            $this->setRelations($info['relations']);
        }

        if (isset($info['on'])) {
            $this->setEvents($info['on']);
        }

        $this->ext = isset($info['ext']) ? $info['ext'] : array();
        
        class_constructor: {
            if (isset($info['constructor']) && $info['constructor']) {
                $this->constructor = $info['constructor'];
            }
            if (isset($info['constructorArgs']) && $info['constructorArgs']) {
                // must come after setFields()
                $this->setConstructorArgs($info['constructorArgs']);
            }
            if (!$this->constructor) {
                $this->constructor = '__construct';
            }
        }

        permissions: {
            if (isset($info['readOnly'])) {
                $this->canInsert = false; 
                $this->canUpdate = false; 
                $this->canDelete = false; 
            }
            else {
                if (isset($info['canInsert'])) {
                    $this->canInsert = !!$info['canInsert'];
                }
                if (isset($info['canUpdate'])) {
                    $this->canUpdate = !!$info['canUpdate'];
                }
                if (isset($info['canDelete'])) {
                    $this->canDelete = !!$info['canDelete'];
                }
            }
        }

        default_field_type: if (isset($info['fieldType'])) {
            $ft = $info['fieldType'];
            if (!is_array($ft)) {
                $ft = array('id'=>$ft);
            }
            $this->fieldType = $ft;
        }
    }

    private function setEvents($events)
    {
        foreach ($events as $event=>$handlers) {
            if (!is_array($handlers)) {
                throw new Exception("Handler $event expected array for meta {$this->class}");
            }
            foreach ($handlers as $h) {
                if ($h instanceof \Closure) {
                    throw new Exception("Handler for $event was an instance of Closure. Unfortunately, Closures aren't serialisable.");
                }
            }
        }
        $this->on = $events;
    }

    private function setRelations($relations)
    {
        foreach ($relations as $id=>$r) {
            if (isset($r['on'])) {
                throw new Exception(
                    "Relation $id used 'on' in class {$this->class}. Please use 'from' and/or 'to' ".
                    "and specify an index name as the value rather than field names"
                );
            }
            if (!isset($r['id'])) {
                $r['id'] = $id;
            }
            if (isset($r['mode'])) {
                if ($r['mode'] == 'auto') {
                    $this->autoRelations[] = $id;
                }
            }
            else {
                $r['mode'] = 'default';
            }
            $this->relations[$id] = $r;
        }
    }

    private function setConstructorArgs($args)
    {
        $checkProperties = [];
        foreach ($args as $arg) {
            $this->constructorArgs[] = $arg;
            if ($arg[0] == 'property') {
                $checkProperties[] = $arg[1];
            }
        }
        if ($diff = array_diff($checkProperties, array_keys($this->getProperties() ?: []))) {
            throw new Exception("Unknown constructor properties ".implode(', ', $diff));
        }
    }

    private function setIndexes($indexes)
    {
        foreach ($indexes as $name=>&$index) {
            if (isset($this->indexes[$name])) {
                throw new Exception("Duplicate index name '$name' on {$this->class}");
            }
            if (!isset($index['key'])) {
                $index['key'] = false;
            }
            if (!isset($index['fields']) || !$index['fields']) {
                throw new \UnexpectedValueException("Misconfigured index '$name': no fields defined");
            }
            $index['fields'] = (array)$index['fields'];
            $this->indexes[$name] = $index;
        }

        // special sauce
        if (isset($this->indexes['primary'])) {
            throw new Exception("Cannot manually declare primary in indexes - set 'primary' in meta info instead");
        }
    }

    private function setFields($inFields)
    {
        $primary = [];
        $indexes = [];
        $fields  = [];
        $reverse = [];
        $autoinc = null;

        foreach ($inFields as $name=>&$field) {
            // TODO: Amiss v6 - remove this check.
            if (is_numeric($name)) {
                throw new \UnexpectedValueException("Received field with numeric index. This is no longer supported. Config: ".serialize($field));
            }

            if ($field === true) {
                $field = [];
            } elseif (is_string($field)) {
                $field = ['name'=>$field];
            }
            if (!is_array($field)) {
                throw new Exception();
            }
            if (!isset($field['name'])) {
                $field['name'] = $name;
            }
            if (!isset($field['id'])) {
                $field['id'] = $name;
            }
            if (!isset($field['required'])) {
                $field['required'] = false;
            }
            if (isset($field['type'])) {
                if (!is_array($field['type'])) {
                    $field['type'] = ['id' => $field['type']];
                }
            }
            else {
                $field['type'] = ['id' => null];
            }
            if ($field['type']['id'] == Mapper::AUTOINC_TYPE) {
                if ($autoinc !== null) {
                    throw new \Exception("More than one autoinc found against meta");
                }
                $autoinc = $field['id'];
            }
            if (isset($field['primary'])) {
                $primary[] = $name;
            }
            if (isset($field['index'])) {
                $index = $field['index'];
                if ($index === true) {
                    $index = [];
                } elseif (!is_array($index)) {
                    throw new Exception("Invalid index '$name': index must either be boolean or an array of index metadata");
                }
                if (!isset($index['fields'])) {
                    $index['fields'] = [$name];
                }
                $indexes[$name] = $index;
            }

            $reverse[$field['name']] = $field['id'];
            $fields[$field['id']] = $field;
        }

        $this->fields = $fields;
        $this->columnMap = $reverse;
        if ($primary) {
            if ($this->primary) {
                throw new Exception("Primary can not be defined at class level and field level simultaneously in class '{$this->class}'");
            }
            $this->primary = $primary;
        }
        if ($indexes) {
            $this->setIndexes($indexes);
        }
        $this->autoinc = $autoinc;

        return $this;
    }

    public function getProperties()
    {
        if ($this->properties === null) {
            foreach ($this->fields as $name=>$field) {
                $field['source'] = 'field';
                $this->properties[$name] = $field;
            }
            foreach ($this->relations as $name=>$relation) {
                if ($relation['mode'] != 'class') {
                    $relation['source'] = 'relation';
                    $this->properties[$name] = $relation;
                }
            }
        }
        return $this->properties;
    }

    function getIndexValue($object, $indexName='primary')
    {
        $foundValue = false;

        if (!isset($this->indexes[$indexName])) {
            throw new Exception("Class {$this->class} doesn't define index $indexName");
        }
        $indexValue = array();
        foreach ($this->indexes[$indexName]['fields'] as $p) {
            if (!isset($this->fields[$p])) {
                throw new \InvalidArgumentException("Unknown field '$p' on {$this->class}");
            }
            $field = $this->fields[$p];

            $value = !isset($field['getter']) 
                ? $object->{$p} 
                : call_user_func(array($object, $field['getter']));

            if ($value) {
                $foundValue = true;
            }
            $indexValue[$p] = $value;
        }
        
        if ($foundValue) {
            return $indexValue;
        }
    }
    
    function getValue($object, $property)
    {
        if ($this->properties === null) {
            $this->getProperties();
        }

        if (!isset($this->properties[$property])) {
            throw new \InvalidArgumentException("Unknown property '$property' on {$this->class}");
        }
        $field = $this->properties[$property];

        $value = !isset($field['getter']) 
            ? $object->{$property} 
            : call_user_func(array($object, $field['getter']));

        return $value;
    }
    
    function setValue($object, $property, $value)
    {
        if ($this->properties === null) {
            $this->getProperties();
        }

        if (!isset($this->properties[$property])) {
            throw new \InvalidArgumentException("Unknown property '$property' on {$this->class}");
        }
        $field = $this->properties[$property];

        if (!isset($field['setter'])) {
            $object->{$property} = $value;
        } else {
            call_user_func(array($object, $field['setter']), $value);
        }
        return $this;
    }
}
