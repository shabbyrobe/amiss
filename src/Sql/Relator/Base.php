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
        $index = (object)[
            'rFields'=>[],
            'params'=>[],
            'ids'=>[],
        ];
        $resultIndex = [];

        foreach ($source as $idx=>$object) {
            $key = [];
            $id = [];
            foreach ($on as $l=>$r) {
                $lField = $lFields[$l];
                $lValue = $object instanceof \stdClass || !isset($lField['getter']) 
                    ? $object->$l 
                    : call_user_func(array($object, $lField['getter']));
                
                $key[] = $lValue;
                
                if (!isset($rFields[$r])) {
                    throw new Exception("Field $r does not exist against relation for ".get_class($object));
                }
                if (!isset($index->params[$l])) {
                    $index->params[$l] = preg_replace('/[^A-Za-z0-9_]/', '', $rFields[$r]['name']);
                    $index->rFields[$l] = $rFields[$r];
                }
                
                $id[$l] = $lValue;
            }

            $key = !isset($key[1]) ? $key[0] : implode('|', $key);
            $index->ids[$key] = $id;
            
            if (!isset($resultIndex[$key])) {
                $resultIndex[$key] = array();
            }
            
            $resultIndex[$key][$idx] = $object;
        }
        
        return array($index, $resultIndex);
    }

    protected function buildRelatedClause($index, $prefix='', &$paramIdx=0)
    {
        $where = '';
        $params = [];
        $tableAlias  = ($prefix ? "{$prefix}." : "");
        $paramPrefix = ($prefix ? "{$prefix}_" : "");

        $firstId = true;
        foreach ($index->ids as $id) {
            if ($firstId) {
                $firstId = false;
            } else {
                $where .= ' OR ';
            }

            $where .= '(';

            $firstValue = true;
            foreach ($id as $l=>$v) {
                if ($firstValue) {
                    $firstValue = false;
                } else {
                    $where .= ' AND ';
                }

                $rName = $index->rFields[$l]['name'];
                $param = ":r_{$paramPrefix}{$index->params[$l]}_".$paramIdx++;
                $where .= $tableAlias.'`'.$rName.'`='.$param;
                $params[$param] = $v;
            }
            $where .= ')';
        }

        return [$where, $params];
    }

    // Transitional - allows OneMany and Assoc to turn 'from' and 'to'
    // relation config into the old-style 'on' so the logic doesn't need
    // to be interfered with yet.
    protected function createOn($meta, $fromIndex, $relatedMeta, $toIndex)
    {
        if (!isset($meta->indexes[$fromIndex])) {
            throw new Exception("Index $fromIndex does not exist on {$meta->id}");
        }
        if (!isset($relatedMeta->indexes[$toIndex])) {
            throw new Exception("Index $toIndex does not exist on {$relatedMeta->id}");
        }

        $on = [];

        // If an index exists, you don't need to join on all of it.
        // This assumes that the indexes are properly numbered. If not, BOOM!
        foreach ($meta->indexes[$fromIndex]['fields'] as $idx=>$fromField) {
            if (!isset($relatedMeta->indexes[$toIndex]['fields'][$idx])) {
                break;
            }
            $on[$fromField] = $relatedMeta->indexes[$toIndex]['fields'][$idx];
        }

        return $on;
    }

    public function resolveFromTo($relation, $relatedMeta)
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

    public function resolveInverse($relation, $relatedMeta)
    {
        if (!isset($relatedMeta->relations[$relation['inverse']])) {
            throw new \Amiss\Exception("Inverse relation {$relation['inverse']} not found on class {$relatedMeta->id}");
        }

        $inverseRel = $relatedMeta->relations[$relation['inverse']];
        $to = isset($inverseRel['from']) ? $inverseRel['from'] : 'primary';
        $from = isset($inverseRel['to']) ? $inverseRel['to'] : 'primary';
        
        return [$from, $to];
    }
}
