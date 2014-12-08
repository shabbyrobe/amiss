<?php
namespace Amiss\Sql\Relator;

use Amiss\Exception;

abstract class Base implements \Amiss\Sql\Relator
{
    public function __construct($manager)
    {
        $this->manager = $manager;
    }
    
    protected function indexSource($source, $on, $lFields, $rFields)
    {
        $resultIndex = array();
        $ids = array();

        foreach ($source as $idx=>$object) {
            $key = array();
            foreach ($on as $l=>$r) {
                $lField = $lFields[$l];
                $lValue = !isset($lField['getter']) ? $object->$l : call_user_func(array($object, $lField['getter']));
                
                $key[] = $lValue;
                
                if (!isset($rFields[$r]))
                    throw new Exception("Field $r does not exist against relation for ".get_class($object));
                
                if (!isset($ids[$l])) {
                    $ids[$l] = array(
                        'values'=>array(), 
                        'rField'=>$rFields[$r], 
                        'param'=>preg_replace('/[^A-Za-z0-9_]/', '', $rFields[$r]['name'])
                    );
                }
                
                $ids[$l]['values'][$lValue] = true;
            }
            
            $key = !isset($key[1]) ? $key[0] : implode('|', $key);
            
            if (!isset($resultIndex[$key]))
                $resultIndex[$key] = array();
            
            $resultIndex[$key][$idx] = $object;
        }
        
        return array($ids, $resultIndex);
    }

    // Transitional - allows OneMany and Assoc to turn 'from' and 'to'
    // relation config into the old-style 'on' so the logic doesn't need
    // to be interfered with yet.
    protected function createOn($meta, $fromIndex, $relatedMeta, $toIndex)
    {
        if (!isset($meta->indexes[$fromIndex]))
            throw new Exception("Index $fromIndex does not exist on {$meta->class}");
        if (!isset($relatedMeta->indexes[$toIndex]))
            throw new Exception("Index $toIndex does not exist on {$relatedMeta->class}");

        $on = [];

        // If an index exists, you don't need to join on all of it.
        // This assumes that the indexes are properly numbered. If not, BOOM!
        foreach ($meta->indexes[$fromIndex]['fields'] as $idx=>$fromField) {
            if (!isset($relatedMeta->indexes[$toIndex]['fields'][$idx]))
                break;
            $on[$fromField] = $relatedMeta->indexes[$toIndex]['fields'][$idx];
        }

        return $on;
    }

    protected function resolveFromTo($relation, $relatedMeta)
    {
        if (isset($relation['inverse'])) {
            $fromTo = $this->resolveInverse($relation, $relatedMeta);
        }
        else {
            $fromTo = [
                isset($relation['from']) ? $relation['from'] : 'primary',
                isset($relation['to']) ? $relation['to'] : 'primary',
            ];
        }
        return $fromTo;
    }

    protected function resolveInverse($relation, $relatedMeta)
    {
        if (!isset($relatedMeta->relations[$relation['inverse']]))
            throw new \Amiss\Exception("Inverse relation {$relation['inverse']} not found on class {$relatedMeta->class}");
        
        $inverseRel = $relatedMeta->relations[$relation['inverse']];
        $to = isset($inverseRel['from']) ? $inverseRel['from'] : 'primary';
        $from = isset($inverseRel['to']) ? $inverseRel['to'] : 'primary';
        
        return [$from, $to];
    }
}
