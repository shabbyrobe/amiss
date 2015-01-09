<?php
namespace Amiss\Sql\Relator;

use Amiss\Sql\Query;
use Amiss\Meta;
use Amiss\Exception;

class OneMany extends Base
{
    private function fetchRelated(Meta $meta, $source, $relation, $criteria)
    {
        if (!$source) { return; }

        $type = $relation[0];
        
        if ($type != 'one' && $type != 'many') {
            throw new Exception("This relator only works with 'one' or 'many' as the type");
        }
        $relatedMeta = $this->manager->mapper->getMeta($relation['of']);

        list ($from, $to) = $this->resolveFromTo($relation, $relatedMeta);

        // TODO: clean up logic to use $from and $to instead of $on
        $on = $this->createOn($meta, $from, $relatedMeta, $to);

        // find query values in source object(s)
        $relatedFields = $relatedMeta->getFields();
        list ($ids, $resultIndex) = $this->indexSource($source, $on, $meta->getFields(), $relatedFields);

        $list = $this->runQuery($ids, $relation, $relatedMeta, $criteria);
        return [$list, $relatedMeta, $relatedFields, $on, $ids, $resultIndex];
    }

    public function getRelated(Meta $meta, $source, array $relation, Query\Criteria $criteria=null)
    {
        $source = [$source];
        list ($result, $relatedMeta, $relatedFields, $on, $ids, $resultIndex) = $this->fetchRelated(
            $meta, $source, $relation, $criteria
        );
        if ($relation[0] == 'one' && $result) {
            $result = current($result);
        }
        return $result;
    }

    public function getRelatedForList(Meta $meta, $source, array $relation, Query\Criteria $criteria=null)
    {
        $type = $relation[0];

        list ($relatedList, $relatedMeta, $relatedFields, $on, $ids, $resultIndex) = $this->fetchRelated(
            $meta, $source, $relation, $criteria
        );

        $result = array();
        foreach ($relatedList ?: [] as $related) {
            $key = array();
            
            foreach ($on as $l=>$r) {
                $rField = $relatedFields[$r];
                $rValue = $related instanceof \stdClass || !isset($rField['getter']) 
                    ? $related->$r 
                    : call_user_func(array($related, $rField['getter']));
                $key[] = $rValue;
            }
            $key = !isset($key[1]) ? $key[0] : implode('|', $key);
            
            foreach ($resultIndex[$key] as $idx=>$lObj) {
                if ('one' == $type) {
                    $result[$idx] = $related;
                }
                elseif ('many' == $type) {
                    if (!isset($result[$idx])) { $result[$idx] = array(); }
                    $result[$idx][] = $related;
                }
            }
        }

        return $result;
    }
    
    private function runQuery($ids, $relation, $relatedMeta, $criteria)
    {
        $query = new Query\Select;
        $where = array();

        foreach ($ids as $l=>$idInfo) {
            $rName = $idInfo['rField']['name'];
            $query->params['r_'.$rName] = array_keys($idInfo['values']);
            $where[] = '`'.str_replace('`', '', $rName).'` IN(:r_'.$idInfo['param'].')';
        }
        $query->where = implode(' AND ', $where);
		if (isset($criteria->args)) {
			$query->args = $criteria->args;
		}

        if ($criteria) {
            list ($cWhere, $cParams) = $criteria->buildClause($relatedMeta);
			if ($cWhere) {
				$query->params = array_merge($cParams, $query->params);
				$query->where .= ' AND ('.$cWhere.')';
			}
            $query->stack = $criteria->stack;
        }

        $list = $this->manager->getList($relation['of'], $query);
        
        return $list;
    }
}
