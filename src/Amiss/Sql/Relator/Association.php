<?php
namespace Amiss\Sql\Relator;

use Amiss\Meta;
use Amiss\Sql\Query;
use Amiss\Exception;

/**
 * TODO: Two stage query? Pros: can allow full use of criteria. Cons: two queries (duh).
 * 
 * Relation definition:
 * 
 * This relator *requires an intermediary object to be available and mapped*.
 * 
 * Required parameters
 * 
 *     $event->relations['artists'] = array('assoc', 'of'=>'Artist', 'via'=>'EventArtist')
 * 
 * If the 'via' object defines multiple relations to the same object, you can declare it (otherwise
 * you'll get the first one of the 'of' type):
 * 
 *     $event->relations['artists'] = array('assoc', 'of'=>'Artist', 'via'=>'EventArtist', 'rel'=>'artist2')
 */
class Association extends Base
{
    public function getRelatedForList(Meta $meta, $source, array $relation, Query\Criteria $criteria=null)
    {
        if (!$source) { return; }

        list ($list, $ids, $resultIndex) = $this->fetchRelated($meta, $source, $relation, $criteria);

        $result = array();
        foreach ($list as $idx=>$related) {
            $id = $ids[$idx];
            $key = !isset($id[1]) ? $id[0] : implode('|', $id['id']);
            
            foreach ($resultIndex[$key] as $idx=>$lObj) {
                if (!isset($result[$idx])) {
                    $result[$idx] = array();
                }
                $result[$idx][] = $related;
            }
        }
        
        return $result;
    }

    public function getRelated(Meta $meta, $source, array $relation, Query\Criteria $criteria=null)
    {
        if (!$source) { return; }

        list ($list, $result, $resultIndex) = $this->fetchRelated($meta, $source, $relation, $criteria);

        return $list; 
    }

    private function fetchRelated($meta, $source, $relation, $criteria)
    {
        if ($criteria && $criteria->params && !$criteria->paramsAreNamed()) {
            throw new \InvalidArgumentException("Association mapper criteria requires named parameters");
        }
        if ($relation[0] != 'assoc') {
            throw new \InvalidArgumentException("This relator only works with 'assoc' as the type");
        }
        
        // find the source object details
        $sourceIsArray = is_array($source) || $source instanceof \Traversable;
        if (!$sourceIsArray) {
            $source = array($source);
        }
        
        $sourceFields = $meta->getFields();
        
        // find all the necessary metadata
        $relatedMeta = $this->manager->getMeta($relation['of']);
        $relatedFields = $relatedMeta->getFields();
        
        $viaMeta = $this->manager->getMeta($relation['via']);
        $viaFields = $viaMeta->getFields();
        $sourceToViaRelationName = isset($relation['rel']) ? $relation['rel'] : null;
        $sourceToViaRelation = null;
        $viaToDestRelationName = null;
        $viaToDestRelation = null;

        if ($sourceToViaRelationName) {
            $sourceToViaRelation = $viaMeta->relations[$relation];
        }

        foreach ($viaMeta->relations as $k=>$v) {
            // inefficient. consider requiring this to be specified rather than inferred
            $of = $this->manager->getMeta($v['of']);
            if ($of->class == $meta->class) {
                if (!$sourceToViaRelation) {
                    $sourceToViaRelation = $v;
                    $sourceToViaRelationName = $k;
                }
            }
            if ($of->class == $relatedMeta->class) {
                if (!$viaToDestRelationName) {
                    $viaToDestRelation = $v;
                    $viaToDestRelationName = $k;
                }
            }
            if ($viaToDestRelation && $sourceToViaRelation) {
                break;
            }
        }
        
        if (!$sourceToViaRelation || !$viaToDestRelation) {
            throw new Exception("Could not find relation between {$meta->class} and {$relation['via']} for relation $relationName");
        }

        { // resolve relation field connections
            list ($sourceToViaFrom, $sourceToViaTo) = $this->resolveFromTo($sourceToViaRelation, $viaMeta);
            $sourceToViaOn = $this->createOn($meta, $sourceToViaTo, $viaMeta, $sourceToViaFrom);

            list ($viaToDestFrom, $viaToDestTo) = $this->resolveFromTo($viaToDestRelation, $viaMeta);
            $viaToDestOn = $this->createOn($viaMeta, $viaToDestFrom, $relatedMeta, $viaToDestTo);
        } 

        // get the source ids, prepare an index to link the relationships
        list($ids, $resultIndex) = $this->indexSource($source, $sourceToViaOn, $sourceFields, $viaFields);
        
        list($query, $params, $sourcePkFields) = $this->buildQuery(
            $ids, $relatedMeta, $viaMeta, $sourceToViaOn, $viaToDestOn, $criteria
        );

        $stmt = $this->manager->execute($query, $params);
        ++$this->manager->queries;
        
        $list = array();
        $ids = array();
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $object = $this->manager->mapper->toObject($row, array(), $relatedMeta);
            
            $list[] = $object;
            $id = array();
            foreach ($sourcePkFields as $field) {
                $id[] = $row[$field];
            }
            $ids[] = $id;
        }

        return [$list, $ids, $resultIndex];
    }
    
    protected function buildQuery($ids, $relatedMeta, $viaMeta, $sourceToViaOn, $viaToDestOn, $criteria)
    {
        $viaFields = $viaMeta->getFields();
        $relatedFields = $relatedMeta->getFields();
        
        $query = new Query\Select();
        
        $where = array();
        foreach ($ids as $l=>$idInfo) {
            $rName = $idInfo['rField']['name'];
            $query->params[$rName] = array_keys($idInfo['values']);
            $where[] = 't2.`'.str_replace('`', '', $rName).'` IN(:'.$idInfo['param'].')';
        }
        $query->where = implode(' AND ', $where);
        
        $queryFields = $query->buildFields($relatedMeta, 't1');
        $sourcePkFields = array();
        foreach ($sourceToViaOn as $l=>$r) {
            $field = $viaFields[$r];
            $sourcePkFields[] = $field['name'];
        }
        
        $joinOn = array();
        foreach ($viaToDestOn as $l=>$r) {
            $joinOn[] = 't2.`'.$viaFields[$l]['name'].'` = t1.`'.$relatedFields[$r]['name'].'`';
        }
        $joinOn = implode(' AND ', $joinOn);
        
        list ($where, $params) = $query->buildClause(null);
        if ($criteria) {
            list ($cWhere, $cParams) = $criteria->buildClause($relatedMeta);
			if ($cWhere) {
                $params = array_merge($cParams, $params);
                $where .= ' AND ('.$cWhere.')';
            }
        }
        
        $sql = "
            SELECT 
                $queryFields, t2.".'`'.implode('`, t2.`', $sourcePkFields).'`'."
            FROM
                {$viaMeta->table} t2
            INNER JOIN
                {$relatedMeta->table} t1
                ON  ({$joinOn})
            WHERE 
                $where
        ";
        
        return array($sql, $params, $sourcePkFields);
    }
}
