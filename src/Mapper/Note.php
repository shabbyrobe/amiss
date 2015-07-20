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
     * @var Nope\Parser
     */
    public $parser;

    public $annotationNamespace = 'amiss';
    
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
            $this->parser = new \Nope\Parser;
        }

        $notes = $this->parser->parseClass($ref, \ReflectionProperty::IS_PUBLIC, \ReflectionMethod::IS_PUBLIC);
        $classNotes = $notes->notes;

        $info = isset($classNotes[$this->annotationNamespace]) ? $classNotes[$this->annotationNamespace] : [];
        $parent = null;

        table: {
            if (!isset($info['table'])) {
                $info['table'] = $this->getDefaultTable($class);
            }
        }

        parent_class: {
            if (isset($info['inherit']) && $info['inherit']) {
                // really important to prepend a backslash - without it, non-namespaced classes
                // fool the class name resolver into thinking they're not fully qualified
                $parentClass = get_parent_class($class);
                if ($parentClass) {
                    $parent = $this->getMeta("\\".$parentClass);
                }
                unset($info['inherit']);
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

        class_indexes: if (isset($info['indexes'])) {
            // TODO: should be in Meta so the Arrays mapper can share it
            foreach ($info['indexes'] as $idxKey=>&$idxDef) {
                if ($idxDef === true) {
                    $idxDef = ['fields'=>[$idxKey]];
                }
            }
            unset($idxDef);
        }
 
        foreach (array('property'=>$notes->properties, 'method'=>$notes->methods) as $noteType=>$noteBag) {
            foreach ($noteBag as $name=>$itemNotes) {
                if (!isset($itemNotes[$this->annotationNamespace])) {
                    continue;
                }

                $itemNotes = $itemNotes[$this->annotationNamespace];
                validate: {
                    if ($diff = array_diff(array_keys($itemNotes), ['has', 'field', 'constructor'])) {
                        throw new \UnexpectedValueException(
                            "Invalid keys found in :amiss field/method annotation: ".implode(', ', $diff)
                        );
                    }
                    if (isset($itemNotes['field']) && isset($itemNotes['has'])) {
                        throw new \UnexpectedValueException(
                            "Invalid class {$class}: relation and a field declared together on {$name}"
                        );
                    }
                }

                field: if (isset($itemNotes['field'])) {
                    $field = $itemNotes['field'];
                    // NOTE: do not ensure $field['name'] is set here - it happens later in
                    // one big hit.

                    // FIXME: this should also happen in the Meta, but we need to guarantee we
                    // are operating on an array to collect getters and setters, etc
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
 
                    $manualKey = isset($field['id']) ? $field['id'] : null;
                    unset($field['id']);
                    $info['fields'][$manualKey ?: $key] = $field;
                }

                field_relation: if (isset($itemNotes['has'])) {
                    $relation = $itemNotes['has'];
                    if (is_string($relation)) {
                        $relation = ["type"=>$relation];
                    } elseif (!is_array($relation)) {
                        throw new \UnexpectedValueException();
                    }
                    if (!isset($relation['type'])) {
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
                    $key = isset($relation['id']) ? $relation['id'] : $key;
                    unset($relation['id']);
                    if (isset($info['relations'][$key])) {
                        throw new \UnexpectedValueException("Duplicate relation {$name} on class {$class}");
                    }
                    $info['relations'][$key] = $relation;
                }

                constructor: if ($noteType == 'method' && isset($itemNotes['constructor'])) {
                    if (isset($info['constructor']) && $info['constructor']) {
                        throw new \UnexpectedValueException("Constructor already declared: {$info['constructor']}");
                    }
                    $info['constructor'] = $name;
                    if ($itemNotes['constructor'] !== true) {
                        if (!is_array($itemNotes['constructor'])) {
                            throw new \UnexpectedValueException();
                        }
                        if (isset($info['constructorArgs']) && $info['constructorArgs']) {
                            throw new \UnexpectedValueException("Constructor args declared at class level and also on method $name.");
                        }
                        $info['constructorArgs'] = $itemNotes['constructor'];
                    }
                }
            }
        }

        if (isset($info['fields'])) {
            $info['fields'] = $this->resolveUnnamedFields($info['fields']);
        }

        return new \Amiss\Meta($class, $info, $parent);
    }

    protected function fillGetterSetter($name, &$itemNotes, $readOnlyAllowed=false)
    {
        $itemNotes['getter'] = $name;
        $methodWithoutPrefix = $name;
        if ($name[0] == 'g' && $name[1] == 'e' && $name[2] == 't') {
            // getFoo -> foo
            $methodWithoutPrefix = substr($name, 3);
        }
        elseif ($name[0] == 'i' && $name[1] == 's') {
            // isFoo -> foo
            $methodWithoutPrefix = substr($name, 2);
        }
        elseif ($name[0] == 'h' && $name[1] == 'a' && $name[2] == 's') {
            // hasFoo -> foo
            $methodWithoutPrefix = substr($name, 3);
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
