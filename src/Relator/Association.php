<?php

namespace Amiss\Relator;

use Amiss\Criteria\Select;

use Amiss\Criteria;

/**
 * Relation definition:
 * 
 * This relator *requires an intermediary object to be available and mapped*.
 * 
 * Required parameters
 * 
 *    $event->relations['artists'] = array('assoc', 'of'=>'Artist', 'via'=>'EventArtist')
 * 
 * If the 'via' object defines multiple relations to the same object, you can declare it (otherwise
 * you'll get the first one of the 'of' type):
 * 
 * 	  $event->relations['artists'] = array('assoc', 'of'=>'Artist', 'via'=>'EventArtist', 'rel'=>'artist2')
 */
class Association
{
	public function getRelated($manager, $source, $relationName, $criteria)
	{
		if (!$source) return;
		
		if ($criteria && !$criteria->paramsAreNamed())
			throw new \InvalidArgumentException("Association mapper criteria requires named parameters");
		
		// find the source object details
		$sourceIsArray = is_array($source) || $source instanceof \Traversable;
		if (!$sourceIsArray) $source = array($source);
		
		$class = !is_object($source[0]) ? $source[0] : get_class($source[0]);
		$meta = $manager->getMeta($class);
		if (!isset($meta->relations[$relationName]))
			throw new Exception("Unknown relation $relationName on $class");
		
		$relation = $meta->relations[$relationName];
		if ($relation[0] != 'assoc')
			throw new \InvalidArgumentException("This relator only works with 'assoc' as the type");
		
		$sourceFields = $meta->getFields();
		
		
		// find all the necessary metadata
		$relatedMeta = $manager->getMeta($relation['of']);
		$relatedFields = $relatedMeta->getFields();
		
		$viaMeta = $manager->getMeta($relation['via']);
		$viaFields = $viaMeta->getFields();
		$sourceToViaRelationName = isset($relation['rel']) ? $relation['rel'] : null;
		$sourceToViaRelation = null;
		$viaToDestRelationName = null;
		$viaToDestRelation = null;
		
		if ($sourceToViaRelationName)
			$sourceToViaRelation = $viaMeta->relations[$relation];
		
		foreach ($viaMeta->relations as $k=>$v) {
			// inefficient. consider requiring this to be specified rather than inferred
			$of = $manager->getMeta($v['of']);
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
			if ($viaToDestRelation && $sourceToViaRelation) break;
		}
		
		if (!$sourceToViaRelation || !$viaToDestRelation)
			throw new \Amiss\Exception("Could not find relation between {$meta->class} and {$relation['via']} for relation $relationName");
		
		$sourceToViaOn = $sourceToViaRelation['on'];
		if (is_string($sourceToViaOn))
			$sourceToViaOn = array($sourceToViaOn=>$sourceToViaOn);
		
		$viaToDestOn = $viaToDestRelation['on'];
		if (is_string($viaToDestOn))
			$viaToDestOn = array($viaToDestOn=>$viaToDestOn);
		
		
		// get the source ids
		$ids = array();
		foreach ($source as $idx=>$object) {
			$key = array();
			foreach ($sourceToViaOn as $l=>$r) {
				$lField = $sourceFields[$l];
				$lValue = !isset($lField['getter']) ? $object->$l : call_user_func(array($object, $lField['getter']));
				
				$key[] = $lValue;
				
				if (!isset($ids[$l])) {
					$ids[$l] = array('values'=>array(), 'rField'=>$viaFields[$r], 'param'=>$manager->sanitiseParam($viaFields[$r]['name']));
				}
				
				$ids[$l]['values'][$lValue] = true;
			}
			
			$key = !isset($key[1]) ? $key[0] : implode('|', $key);
			
			if (!isset($resultIndex[$key]))
				$resultIndex[$key] = array();
			
			$resultIndex[$key][$idx] = $object;
		}
		
		$query = new Select();
	
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
			$field = $viaMeta->getField($r);
			$sourcePkFields[] = $field['name'];
		}
		
		$joinOn = array();
		foreach ($viaToDestOn as $l=>$r) {
			$joinOn[] = 't2.`'.$viaFields[$l]['name'].'` = t1.`'.$relatedFields[$r]['name'].'`';
		}
		$joinOn = implode(' AND ', $joinOn);
		
		list ($where, $params) = $query->buildClause();
		if ($criteria) {
			list ($cWhere, $cParams) = $criteria->buildClause();
			$params = array_merge($cParams, $params);
			$where .= ' AND ('.$cWhere.')';
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
				
		$stmt = $manager->execute($sql, $params);
		
		$list = array();
		$ids = array();
		while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
			$object = $manager->mapper->createObject($relatedMeta, $row, array());
			$manager->mapper->populateObject($relatedMeta, $object, $row);
			
			$list[] = $object;
			$id = array();
			foreach ($sourcePkFields as $field) {
				$id[] = $row[$field];
			}
			$ids[] = $id;
		}
		
		// prepare the result
		$result = null;
		if (!$sourceIsArray) {
			$result = $list;
		}
		else {
			$result = array();
			foreach ($list as $idx=>$related) {
				$id = $ids[$idx];
				$key = !isset($id[1]) ? $id[0] : implode('|', $id['id']);
				
				foreach ($resultIndex[$key] as $idx=>$lObj) {
					if (!isset($result[$idx])) $result[$idx] = array();
					$result[$idx][] = $related;
				}
			}
		}
		
		return $result;
	}
}
