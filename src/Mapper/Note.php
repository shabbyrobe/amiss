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

        $setters = array();
        $relationNotes = array();
        
        foreach (array('property'=>$notes->properties, 'method'=>$notes->methods) as $type=>$noteBag) {
            foreach ($noteBag as $name=>$itemNotes) {
                $itemNotes = $itemNotes['amiss'];

                $key = null;
                $field = null;
                $relationNote = null;

                if (isset($itemNotes['field'])) {
                    $field = $itemNotes['field'] !== true ? $itemNotes['field'] : true;
                }
                if (isset($itemNotes['primary']) && !$field) {
                    $field = true;
                }

                key_find: if ($field !== null) {
                    // $key is set by this block
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

                field_primary: if ($key && isset($itemNotes['primary'])) {
                    $info['primary'][] = $key;
                }

                field_index: if ($key && (isset($itemNotes['index']) || isset($itemNotes['key']))) {
                    $indexNote = isset($itemNotes['index']) ? $itemNotes['index'] : null;
                    $keyNote   = isset($itemNotes['key'])   ? $itemNotes['key']   : null;
                    if ($indexNote && $keyNote) {
                        throw new Exception("Invalid field $key: cannot specify both index and key");
                    }

                    $indexNote = $indexNote ?: $keyNote;
                    $indexName = null;
                    if ($indexNote === true) {
                        $indexName = $key;
                    } elseif (is_string($indexNote)) {
                        $indexName = $indexNote;
                    } else {
                        throw new Exception();
                    }
                    if (isset($info['indexes'][$indexName])) {
                        throw new Exception("Index $indexName already defined");
                    }
                    $info['indexes'][$indexName] = [
                        'fields'=>[$key],
                        'key'=>$keyNote == true,
                    ];
                }
                
                field_relation: if (isset($itemNotes['has'])) {
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

                constructor: if ($type == 'method' && isset($itemNotes['constructor'])) {
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

        if ($relationNotes) {
            foreach ($this->buildRelations($relationNotes) as $relKey=>$relDef) {
                if (isset($info['relations'][$relKey])) {
                    throw new \UnexpectedValueException();
                }
                $info['relations'][$relKey] = $relDef;
            }
        }
        
        $info['fields'] = $this->resolveUnnamedFields($info['fields']);
        
        return new \Amiss\Meta($class, $table, $info, $parent);
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
        
        foreach ($relationNotes as $name=>$info) {
            $relation = $info['has'];
            if (is_string($relation)) {
                $relation = [$relation];
            }
            else {
                if (!isset($relation['type'])) {
                    throw new \UnexpectedValueException("Relation $name missing 'type'");
                }
                $type = $relation['type'];
                unset($relation['type']);
                array_unshift($relation, $type);
            }
            if (isset($info['getter'])) {
                list($name, $relation['getter'], $relation['setter']) = $this->findGetterSetter($name, $info, !'readOnly');
            }
            $relations[$name] = $relation;
        }
        return $relations;
    }
}
