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

    private $mapsCache = [];
    
    public function __construct($cache=null, $parser=null)
    {
        parent::__construct();
        
        $this->parser = $parser;
        $this->cache = $cache;
    }

    protected function createMeta($class)
    {
        $meta = null;

        $key = "note-class-$class";
        if ($this->cache) {
            $meta = $this->cache->get($key);
        }
        if (!$meta) {
            $meta = $this->loadMeta($class);
            $this->mapsCache[$class] = true;
            if ($this->cache) {
                $this->cache->set($key, $meta);
            }
        }
        return $meta;
    }

    function canMap($id)
    {
        if (isset($this->mapsCache[$id])) {
            return $this->mapsCache[$id];
        }

        $key = "note-maps-$id";
        $maps = null;
        if ($this->cache) {
            $maps = $this->cache->get($key);
        }

        if ($maps === null) {
            $maps = $this->loadMeta($id) == true;
            $this->mapsCache[$id] = $maps;
            if ($this->cache) {
                $this->cache->set($key, $maps);
            }
        }
        return $maps;
    }
    
    protected function loadMeta($class)
    {
        $ref = new \ReflectionClass($class);
        
        if (!$this->parser) {
            $this->parser = new \Nope\Parser;
        }

        $notes = $this->parser->parseHierarchy($ref, \ReflectionProperty::IS_PUBLIC, \ReflectionMethod::IS_PUBLIC);
        $classNotes = $notes->notes;

        if (!isset($classNotes[$this->annotationNamespace])) {
            return null;
        }
        
        $info = $classNotes[$this->annotationNamespace];
        if ($info === true) {
            $info = [];
        }

        table: {
            if (!isset($info['table'])) {
                $info['table'] = $this->getDefaultTable($class);
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
                    goto next_note_bag;
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
                    } elseif ($field === false) {
                        // allows us to remove properties from consideration in child classes, i.e. :amiss = {"field": false};
                        goto next_note_bag;
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
                    } elseif ($relation === false) {
                        goto next_note_bag;
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

            next_note_bag:
            }
        }

        if (isset($info['fields'])) {
            $info['fields'] = $this->resolveUnnamedFields($info['fields']);
        }

        return new \Amiss\Meta(ltrim($class, '\\'), $info);
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
