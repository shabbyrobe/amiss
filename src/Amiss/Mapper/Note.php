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
        
        if (!$this->parser)
            $this->parser = new \Amiss\Note\Parser;
        
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
        );

        if (isset($classNotes['index'])) {
            $info['indexes'] = $classNotes['index'];
        }

        if (isset($classNotes['constructor']))
            $info['constructor'] = $classNotes['constructor'];

        $setters = array();
        
        $relationNotes = array();
        
        $fieldIndexLengths = [];
        foreach (array('property'=>$notes->properties, 'method'=>$notes->methods) as $type=>$noteBag) {
            foreach ($noteBag as $name=>$itemNotes) {
                $field = null;
                $relationNote = null;
                
                if (isset($itemNotes['field']))
                    $field = $itemNotes['field'] !== true ? $itemNotes['field'] : false;
                
                if (isset($itemNotes['has']))
                    $relationNote = $itemNotes['has'];

                if (isset($itemNotes['primary'])) {
                    $info['primary'][] = $name;
                    if (!$field) $field = $name;
                }
                
                if ($field !== null) {
                    $fieldInfo = array();
                    
                    if ($type == 'method') {
                        list($name, $fieldInfo['getter'], $fieldInfo['setter']) = 
                            $this->findGetterSetter($name, $itemNotes, !!'readOnly'); 
                    }
                    
                    if ($field && $field != $name) {
                        $fieldInfo['name'] = $field;
                    }

                    $fieldInfo['type'] = isset($itemNotes['type']) 
                        ? $itemNotes['type'] 
                        : null
                    ;

                    $info['fields'][$name] = $fieldInfo;
                }

                // index needs to come after getter/setter checking as the name gets changed
                if (isset($itemNotes['index'])) {
                    $indexNote = $itemNotes['index'];
                    if ($indexNote === true) {
                        $indexNote = [$name=>true];
                    }
                    elseif (is_string($indexNote)) {
                        $indexNote = [$indexNote=>true];
                    }

                    foreach ($indexNote as $k=>$seq) {
                        if ($seq === true)
                            $seq = 0;
                        else
                            $seq = (int) $seq;

                        if (isset($info['indexes'][$k]['fields'][$seq]))
                            throw new Exception("Duplicate sequence $seq for index $k");

                        // hacky increment even if the key doesn't exist
                        $c = &$fieldIndexLengths[$k]; $c = ((int)$c) + 1;
                        $info['indexes'][$k]['fields'][$seq] = $name;
                    }
                }
                
                if ($relationNote !== null) {
                    if ($field)
                        throw new \UnexpectedValueException("Invalid class {$class}: relation and a field declared together on {$name}");
                    
                    if ($type == 'method') {
                        if (!isset($itemNotes['getter'])) {
                            $itemNotes['getter'] = $name;
                        }
                    }
                    $relationNotes[$name] = $itemNotes;
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
    
    protected function findGetterSetter($name, $itemNotes, $readOnlyAllowed=false)
    {
        $getter = $name;
        $methodWithoutPrefix = $name[0] == 'g' && $name[1] == 'e' && $name[2] == 't' ? substr($name, 3) : $name;
        $name = lcfirst($methodWithoutPrefix);

        if ($readOnlyAllowed && (isset($itemNotes['readOnly']) || isset($itemNotes['readonly'])))
            $setter = false;
        else
            $setter = !isset($itemNotes['setter']) ? 'set'.$methodWithoutPrefix : $itemNotes['setter'];
        
        return array($name, $getter, $setter);
    }
    
    protected function buildRelations($relationNotes)
    {
        $relations = array();
        
        if (!$this->parser)
            $this->parser = new \Amiss\Note\Parser;
        
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
                
            if (isset($info['getter']))
                list($name, $relation['getter'], $relation['setter']) = $this->findGetterSetter($name, $info, !'readOnly');
            
            $relations[$name] = $relation;
        }
        return $relations;
    }
}
