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

        $info = isset($classNotes['amiss']) ? $classNotes['amiss'] : [];

        table: {
            $table = isset($info['table']) ? $info['table'] : $this->getDefaultTable($class);
            unset($info['table']);
        }

        parent_class: {
            $parentClass = get_parent_class($class);
            $parent = null;
            if ($parentClass) {
                $parent = $this->getMeta($parentClass);
            }
        }

        class_relations: if (isset($info['relations'])) {
            foreach ($info['relations'] as $relKey=>&$relDef) {
                $type = $relDef['type'];
                unset($relDef['type']);
                array_unshift($relDef, $type);

                if (!is_array($relDef)) {
                    throw new \Exception("Relation $relKey was not valid in class $class");
                }
                $relDef['mode'] = 'class';
            }
            unset($relDef);
        }

        indexes: if (isset($info['indexes'])) {
            foreach ($info['indexes'] as $idxKey=>&$idxDef) {
                if ($idxDef === true) {
                    $idxDef = ['fields'=>[$idxKey]];
                }
            }
            unset($idxDef);
        }
 
        foreach (array('property'=>$notes->properties, 'method'=>$notes->methods) as $noteType=>$noteBag) {
            foreach ($noteBag as $name=>$itemNotes) {
                if (!isset($itemNotes['amiss'])) {
                    continue;
                }

                $itemNotes = $itemNotes['amiss'];

                field: if (isset($itemNotes['field'])) {
                    $field = $itemNotes['field'];
                    if ($field === true) {
                        $field = [];
                    } elseif (is_string($field)) {
                        $field = ['name'=>$field];
                    } elseif (!is_array($field)) {
                        throw new \UnexpectedValueException();
                    }
                    
                    if ($noteType == 'method') {
                        $key = $this->fillGetterSetter($name, $field, !!'readOnly'); 
                    } else {
                        $key = $name;
                    }
                    
                    if (!isset($field['name'])) {
                        $field['name'] = $key;
                    }

                    $info['fields'][$key] = $field;
                }
                
                field_relation: if (isset($itemNotes['has'])) {
                    if (isset($itemNotes['field'])) {
                        throw new \UnexpectedValueException(
                            "Invalid class {$class}: relation and a field declared together on {$name}"
                        );
                    }
                    $relation = $itemNotes['has'];
                    if (is_string($relation)) {
                        $relation = [$relation];
                    } elseif (!isset($relation['type'])) {
                        throw new \UnexpectedValueException("Relation $name missing 'type'");
                    }
                    $type = $relation['type'];
                    unset($relation['type']);
                    array_unshift($relation, $type);

                    if ($noteType == 'method') {
                        $key = $this->fillGetterSetter($name, $relation, !!'readOnly'); 
                    } else {
                        $key = $name;
                    }
                    if (isset($info['relations'][$key])) {
                        throw new \UnexpectedValueException("Duplicate relation {$name} on class {$class}");
                    }
                    $info['relations'][$key] = $relation;
                }

                constructor: if ($noteType == 'method' && isset($itemNotes['constructor'])) {
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

        if (isset($info['fields'])) {
            $info['fields'] = $this->resolveUnnamedFields($info['fields']);
        }

        return new \Amiss\Meta($class, $table, $info, $parent);
    }

    protected function fillGetterSetter($name, &$itemNotes, $readOnlyAllowed=false)
    {
        $itemNotes['getter'] = $name;
        $methodWithoutPrefix = $name;
        if ($name[0] == 'g' && $name[1] == 'e' && $name[2] == 't') {
            $methodWithoutPrefix = substr($name, 3);
        } elseif ($name[0] == 'i' && $name[1] == 's') {
            $methodWithoutPrefix = substr($name, 2);
        }

        $name = lcfirst($methodWithoutPrefix);

        if ($readOnlyAllowed && (isset($itemNotes['readOnly']) || isset($itemNotes['readonly']))) {
            $itemNotes['setter'] = false;
        } else {
            $itemNotes['setter'] = !isset($itemNotes['setter']) ? 'set'.$methodWithoutPrefix : $itemNotes['setter'];
        }
        return $name;
    }    
}
