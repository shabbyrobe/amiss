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

        list ($list, $ids, $resultIndex) = $this->fetchRelated($meta, $source, $relation, $criteria);

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
        
        $sourceFields = $meta->fields;
        
        // find all the necessary metadata
        $relatedMeta = $this->manager->getMeta($relation['of']);
        $relatedFields = $relatedMeta->fields;
        
        $viaMeta = $this->manager->getMeta($relation['via']);
        $viaFields = $viaMeta->fields;
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
        list($index, $resultIndex) = $this->indexSource($source, $sourceToViaOn, $sourceFields, $viaFields);
        
        list($query, $params, $sourcePkFields, $props) = $this->buildQuery(
            $index, $relatedMeta, $viaMeta, $sourceToViaOn, $viaToDestOn, $criteria
        );
        if ($props) {
            $params = $this->manager->mapper->formatParams($relatedMeta, $props, $params);
        }

        $stmt = $this->manager->connector->prepare($query)->execute($params);

        $list = array();
        $ids = array();
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $object = $this->manager->mapper->mapRowToObject($row, array(), $relatedMeta);
            
            $list[] = $object;
            $id = array();
            foreach ($sourcePkFields as $field) {
                $id[] = $row[$field];
            }
            $ids[] = $id;
        }

        return [$list, $ids, $resultIndex];
    }
    
    protected function buildQuery($index, $relatedMeta, $viaMeta, $sourceToViaOn, $viaToDestOn, $criteria)
    {
        $viaFields = $viaMeta->fields;
        $relatedFields = $relatedMeta->fields;
        
        $query = new Query\Select();
        
        list($query->where, $query->params) = $this->buildRelatedClause($index, 't2');
        if ($criteria instanceof Query\Select) {
            $query->page      = $criteria->page;
            $query->limit     = $criteria->limit;
            $query->args      = $criteria->args;
            $query->offset    = $criteria->offset;
            $query->order     = $criteria->order;
            $query->forUpdate = $criteria->forUpdate;
        }

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
        
        list ($where, $params, $props) = $query->buildClause(null);
        if ($criteria) {
            list ($cWhere, $cParams, $cProps) = $criteria->buildClause($relatedMeta);
            if ($cWhere) {
                $params = array_merge($cParams, $params);
                $props = array_merge($props, $cProps);
                $where .= ' AND ('.$cWhere.')';
            }
        }
        
        $order = $query->buildOrder($relatedMeta, 't1');
        list ($limit, $offset) = $query->getLimitOffset();

        $vt = ($viaMeta->schema     ? "`{$viaMeta->schema}`."     : null)."`{$viaMeta->table}`";
        $rt = ($relatedMeta->schema ? "`{$relatedMeta->schema}`." : null)."`{$relatedMeta->table}`";
        $sql = "
            SELECT 
                $queryFields, t2.".'`'.implode('`, t2.`', $sourcePkFields).'`'."
            FROM
                $vt t2
            INNER JOIN
                $rt t1
                ON  ({$joinOn})
            WHERE $where "
            .($order  ? "ORDER BY $order "         : '').' '
            .($limit  ? "LIMIT  ".(int)$limit." "  : '').' '
            .($offset ? "OFFSET ".(int)$offset." " : '').' '

            .($query->forUpdate ? 'FOR UPDATE' : '')
        ;
        
        return array($sql, $params, $sourcePkFields, $props);
    }
}
