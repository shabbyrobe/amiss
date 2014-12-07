<?php
namespace Amiss\Sql\Relator;

use Amiss\Sql\Criteria;
use Amiss\Exception;

class OneMany extends Base
{
    public function getRelated($source, $relationName, $criteria=null, $stack=[])
    {
        if (!$source) return;

        $sourceIsArray = is_array($source) || $source instanceof \Traversable;
        if (!$sourceIsArray) $source = array($source);
        
        $class = !is_object($source[0]) ? $source[0] : get_class($source[0]);
        $meta = $this->manager->getMeta($class);
        if (!isset($meta->relations[$relationName])) {
            throw new \Amiss\Exception("Unknown relation $relationName on $class");
        }
        
        $relation = $meta->relations[$relationName];
        $type = $relation[0];
        
        if ($type != 'one' && $type != 'many')
            throw new \InvalidArgumentException("This relator only works with 'one' or 'many' as the type");
        
        if ($type == 'one' && $criteria)
            throw new \InvalidArgumentException("There's no point passing criteria for a one-to-one relation.");
        
        $relatedMeta = $this->manager->getMeta($relation['of']);

        if (isset($relation['inverse'])) {
            list ($from, $to) = $this->resolveInverse($relation, $relatedMeta);
        }
        else {
            $from = isset($relation['from']) ? $relation['from'] : 'primary';
            $to = isset($relation['to']) ? $relation['to'] : 'primary';
        }

        if (isset($relation['on']))
            throw new Exception("Relation $relationName used 'on' in class {$meta->class}. Please use 'from' and/or 'to'");

        // TODO: clean up logic below to use $from and $to instead of $on
        if (!isset($meta->indexes[$from]))
            throw new Exception("Index $from does not exist on {$meta->class}");
        if (!isset($relatedMeta->indexes[$to]))
            throw new Exception("Index $to does not exist on {$relatedMeta->class}");

        // If an index exists, you don't need to join on all of it.
        // This assumes that the indexes are properly numbered. If not, BOOM!
        $on = [];
        foreach ($meta->indexes[$from]['fields'] as $idx=>$fromField) {
            if (!isset($relatedMeta->indexes[$to]['fields'][$idx]))
                break;
            $on[$fromField] = $relatedMeta->indexes[$to]['fields'][$idx];
        }

        // find query values in source object(s)
        $relatedFields = $relatedMeta->getFields();
        list ($ids, $resultIndex) = $this->indexSource(
            $source, $on, $meta->getFields(), $relatedFields
        );

        $list = $this->runQuery($ids, $relation, $relatedMeta, $criteria, $stack);

        // prepare the result
        $result = null;
        if (!$sourceIsArray) {
            if ($type == 'one') {
                if ($list) $result = current($list);
            }
            else {
                $result = $list;
            }
        }
        else {
            $result = array();
            foreach ($list ?: [] as $related) {
                $key = array();
                
                foreach ($on as $l=>$r) {
                    $rField = $relatedFields[$r];
                    $rValue = !isset($rField['getter']) ? $related->$r : call_user_func(array($related, $rField['getter']));
                    $key[] = $rValue;
                }
                $key = !isset($key[1]) ? $key[0] : implode('|', $key);
                
                foreach ($resultIndex[$key] as $idx=>$lObj) {
                    if ('one' == $type) {
                        $result[$idx] = $related;
                    }
                    elseif ('many' == $type) {
                        if (!isset($result[$idx])) $result[$idx] = array();
                        $result[$idx][] = $related;
                    }
                }
            }
        }

        return $result;
    }
    
    private function resolveInverse($relation, $relatedMeta)
    {
        if (!isset($relatedMeta->relations[$relation['inverse']]))
            throw new \Amiss\Exception("Inverse relation {$relation['inverse']} not found on class {$relatedMeta->class}");
        
        $inverseRel = $relatedMeta->relations[$relation['inverse']];
        $to = isset($inverseRel['from']) ? $inverseRel['from'] : 'primary';
        $from = isset($inverseRel['to']) ? $inverseRel['to'] : 'primary';
        
        return [$from, $to];
    }

    private function runQuery($ids, $relation, $relatedMeta, $criteria, $stack)
    {
        $query = new Criteria\Select;
        $where = array();

        foreach ($ids as $l=>$idInfo) {
            $rName = $idInfo['rField']['name'];
            $query->params['r_'.$rName] = array_keys($idInfo['values']);
            $where[] = '`'.str_replace('`', '', $rName).'` IN(:r_'.$idInfo['param'].')';
        }
        $query->where = implode(' AND ', $where);
        
        if ($criteria) {
            list ($cWhere, $cParams) = $criteria->buildClause($relatedMeta);
            $query->params = array_merge($cParams, $query->params);
            $query->where .= ' AND ('.$cWhere.')';
        }

        $query->stack = $stack;
        $list = $this->manager->getList($relation['of'], $query);
        
        return $list;
    }
}
