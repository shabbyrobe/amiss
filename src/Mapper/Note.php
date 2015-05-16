<?php
namespace Amiss\Mapper;

use Amiss\Exception;

/**
 * @package Mapper
 */
class Note extends \Amiss\Mapper\Base
{
    private $cache;
    
    /**
     * @var Amiss\Note\Parser
     */
    public $parser;
    
    public function __construct($cache=null, $parser=null)
    {
        parent::__construct();
        
        $this->parser = $parser;
        $this->cache = $cache;
    }
    
    protected function createMeta($class)
    {
        $meta = null;
        
        if ($this->cache) {
            $meta = $this->cache->get($class);
        }
        if (!$meta) {
            $meta = $this->loadMeta($class);
            if ($this->cache) {
                $this->cache->set($class, $meta);
            }
        }
        
        return $meta;
    }
    
    protected function loadMeta($class)
    {
        $ref = new \ReflectionClass($class);
        
        if (!$this->parser) {
            $this->parser = new \Amiss\Note\Parser;
        }
        
        $notes = $this->parser->parseClass($ref);
        $classNotes = $notes->notes;
        $table = isset($classNotes['table']) ? $classNotes['table'] : $this->getDefaultTable($class);

        $parentClass = get_parent_class($class);
        $parent = null;
        if ($parentClass) {
            $parent = $this->getMeta($parentClass);
        }
        
        $info = array(
            'primary'=>array(),
            'fields'=>array(),
            'indexes'=>array(),
            'relations'=>array(),
            'ext'=>isset($classNotes['ext']) ? $classNotes['ext'] : null,
            'defaultFieldType'=>isset($classNotes['fieldType']) ? $classNotes['fieldType'] : null,
            'constructor'=>null,
            'constructorArgs'=>[],
        );

        if (isset($classNotes['readOnly']) && $classNotes['readOnly']) {
            $info['readOnly'] = true;
        }
        if (isset($classNotes['canInsert'])) {
            $info['canInsert'] = !!$classNotes['canInsert'];
        }
        if (isset($classNotes['canUpdate'])) {
            $info['canUpdate'] = !!$classNotes['canUpdate'];
        }
        if (isset($classNotes['canDelete'])) {
            $info['canDelete'] = !!$classNotes['canDelete'];
        }

        if (isset($classNotes['index'])) {
            foreach ($classNotes['index'] as $key=>$index) {
                if ($index === true) {
                    $index = ['fields'=>[$key]];
                }
                $info['indexes'][$key] = $index;
            }
        }

        if (isset($classNotes['constructor'])) {
            if (is_string($classNotes['constructor'])) {
                $info['constructor'] = $classNotes['constructor'];
            }
            elseif (is_array($classNotes['constructor'])) {
                $info['constructor'] = $classNotes['constructor']['name'];
                $info['constructorArgs'] = $this->parseConstructorArgs($classNotes['constructor']['args']);
            }
            else {
                throw new \Exception("Constructor annotation for class {$class} must be string or array");
            }
        }
        
        $relationNotes = array();

        class_relations: {
            if (isset($classNotes['relation'])) {
                foreach ($classNotes['relation'] as $key=>$def) {
                    if (!is_array($def)) {
                        throw new \Exception("Relation $key was not valid in class $class");
                    }
                    $type = key($def);
                    if (!is_array($def[$type])) {
                        throw new \Exception("Relation $key was not valid in class $class");
                    }
                    if (isset($def[$type]['mode'])) {
                        throw new \Exception("Mode {$def['mode']} not valid for class-level relation {$key}");
                    }
                    $def[$type]['mode'] = 'class';
                    $relationNotes[$key] = ['has'=>$def];
                }
            }
        }

        $setters = array();
        
        $fieldIndexLengths = [];
        foreach (array('property'=>$notes->properties, 'method'=>$notes->methods) as $type=>$noteBag) {
            foreach ($noteBag as $name=>$itemNotes) {
                $key = null;
                $field = null;
                $relationNote = null;

                if (isset($itemNotes['field'])) {
                    $field = $itemNotes['field'] !== true ? $itemNotes['field'] : true;
                }
                if (isset($itemNotes['primary']) && !$field) {
                    $field = true;
                }

                // $key is set by this block
                if ($field !== null) {
                    $fieldInfo = array();

                    if ($type == 'method') {
                        list ($key, $fieldInfo['getter'], $fieldInfo['setter']) = $this->findGetterSetter($name, $itemNotes, !!'readOnly'); 
                    } else {
                        $key = $name;
                    }
                    if ($field !== true) {
                        $fieldInfo['name'] = $field;
                    }
                    $fieldInfo['type'] = isset($itemNotes['type']) 
                        ? $itemNotes['type'] 
                        : null
                    ;
                    $info['fields'][$key] = $fieldInfo;
                }

                if ($key && isset($itemNotes['primary'])) {
                    $info['primary'][] = $key;
                }

                if ($key && isset($itemNotes['index'])) {
                    $indexNote = $itemNotes['index'];
                    if ($indexNote === true) {
                        $indexNote = [$key=>true];
                    }
                    elseif (is_string($indexNote)) {
                        $indexNote = [$indexNote=>true];
                    }

                    foreach ($indexNote as $k=>$seq) {
                        $seq = $seq === true ? 0 : (int)$seq;

                        if (isset($info['indexes'][$k]['fields'][$seq])) {
                            throw new Exception("Duplicate sequence $seq for index $k");
                        }
                        // hacky increment even if the key doesn't exist
                        $c = &$fieldIndexLengths[$k]; $c = ((int)$c) + 1;
                        $info['indexes'][$k]['fields'][$seq] = $key;
                    }
                }
                
                if (isset($itemNotes['has'])) {
                    if ($field) {
                        throw new \UnexpectedValueException(
                            "Invalid class {$class}: relation and a field declared together on {$name}"
                        );
                    }

                    if ($type == 'method' && (!isset($itemNotes['getter']) || !$itemNotes['getter'])) {
                        $itemNotes['getter'] = $name;
                    }

                    if (isset($relationNotes[$name])) {
                        throw new \UnexpectedValueException("Duplicate relation {$name} on class {$class}");
                    }
                    $relationNotes[$name] = $itemNotes;
                }

                // look for the constructor!
                if ($type == 'method' && isset($itemNotes['constructor'])) {
                    if ($info['constructor']) {
                        throw new \UnexpectedValueException("Constructor already declared: {$info['constructor']}");
                    }
                    $info['constructor'] = $name;
                    if (isset($itemNotes['constructor']['args'])) {
                        $info['constructorArgs'] = $this->parseConstructorArgs($itemNotes['constructor']['args']);
                    }
                }
            }
        }

        // The indexes may be added out of order:
        // $a[1] = 'b'; $a[0] = 'a'; == [1 => 'b', 0 => 'a']!
        foreach ($fieldIndexLengths as $name=>$length) {
            if ($length > 1) {
                $f = $info['indexes'][$name]['fields'];
                ksort($f);
                $info['indexes'][$name]['fields'] = array_values($f);
            }
        }

        if ($relationNotes) {
            $info['relations'] = $this->buildRelations($relationNotes);
        }
        
        $info['fields'] = $this->resolveUnnamedFields($info['fields']);
        
        return new \Amiss\Meta($class, $table, $info, $parent);
    }

    protected function parseConstructorArgs($constructorArgs)
    {
        $args = [];
        foreach ($constructorArgs as $idx=>$arg) {
            $split = preg_split('/:\s*/', $arg, 2);
            if (!$split[0] || !isset($split[1]) || $split[1] === null || $split[1] === '') {
                throw new \UnexpectedValueException("Invalid arg specification. Expected 'type:id', found '$arg'");
            }
            $args[$idx] = [$split[0], $split[1]];
        }
        return $args;
    }
    
    protected function findGetterSetter($name, $itemNotes, $readOnlyAllowed=false)
    {
        $getter = $name;
        $methodWithoutPrefix = $name[0] == 'g' && $name[1] == 'e' && $name[2] == 't' ? substr($name, 3) : $name;
        $name = !isset($itemNotes['name']) ? lcfirst($methodWithoutPrefix) : $itemNotes['name'];

        if ($readOnlyAllowed && (isset($itemNotes['readOnly']) || isset($itemNotes['readonly']))) {
            $setter = false;
        } else {
            $setter = !isset($itemNotes['setter']) ? 'set'.$methodWithoutPrefix : $itemNotes['setter'];
        }
        return array($name, $getter, $setter);
    }
    
    protected function buildRelations($relationNotes)
    {
        $relations = array();
        
        if (!$this->parser) {
            $this->parser = new \Amiss\Note\Parser;
        }
        foreach ($relationNotes as $name=>$info) {
            $relation = $info['has'];
            if (is_string($relation)) {
                $relation = array($relation);
            }
            else {
                $id = key($relation);
                $relation = current($relation);
                array_unshift($relation, $id);
            }

            if (isset($info['getter'])) {
                list($name, $relation['getter'], $relation['setter']) = $this->findGetterSetter($name, $info, !'readOnly');
            }
            $relations[$name] = $relation;
        }
        return $relations;
    }
}
