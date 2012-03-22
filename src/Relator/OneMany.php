<?php

namespace Amiss\Relator;

use Amiss\Criteria;

class OneMany
{
	public function getRelated($manager, $source, $relationName)
	{
		if (!$source) return;
		
		$sourceIsArray = is_array($source) || $source instanceof \Traversable;
		if (!$sourceIsArray) $source = array($source);
		
		$class = !is_object($source[0]) ? $source[0] : get_class($source[0]);
		$meta = $manager->getMeta($class);
		if (!isset($meta->relations[$relationName])) {
			throw new Exception("Unknown relation $relationName on $class");
		}
		
		$relation = $meta->relations[$relationName];
		$type = $relation[0];
		
		if ($type != 'one' && $type != 'many')
			throw new \InvalidArgumentException("This relator only works with 'one' or 'many' as the type");
		
		$relatedMeta = $manager->getMeta($relation['to']);
		
		// prepare the relation's "on" field
		$on = null;
		if (isset($relation['on']))
			$on = $relation['on'];
		else {
			if ('one'==$type)
				throw new Exception("One-to-one relation {$relationName} on class {$class} does not declare 'on' field");
			else {
				$on = array();
				foreach ($meta->primary as $p) {
					$on[$p] = $p;
				}
			}
		}
		
		if (!is_array($on)) $on = array($on=>$on);
		
		// populate the 'on' with necessary data
		$relatedFields = $relatedMeta->getFields();
		foreach ($on as $l=>$r) {
			$on[$l] = $relatedFields[$r];
		}
		
		// find query values in source object(s)
		$fields = $meta->getFields();
		$resultIndex = array();
		$ids = array();
		foreach ($source as $idx=>$object) {
			$key = array();
			foreach ($on as $l=>$r) {
				$lField = $fields[$l];
				$lValue = !isset($lField['getter']) ? $object->$l : call_user_func(array($object, $lField['getter']));
				
				$key[] = $lValue;
				
				if (!isset($ids[$l])) {
					$ids[$l] = array('values'=>array(), 'rField'=>$r, 'param'=>$manager->sanitiseParam($r['name']));
				}
				
				$ids[$l]['values'][$lValue] = true;
			}
			
			$key = !isset($key[1]) ? $key[0] : implode('|', $key);
			
			if (!isset($resultIndex[$key]))
				$resultIndex[$key] = array();
			
			$resultIndex[$key][$idx] = $object;
		}
		
		// build query
		$criteria = new Criteria\Select;
		$where = array();
		foreach ($ids as $l=>$idInfo) {
			$rName = $idInfo['rField']['name'];
			$criteria->params[$rName] = array_keys($idInfo['values']);
			$where[] = '`'.str_replace('`', '', $rName).'` IN(:'.$idInfo['param'].')';
		}
		$criteria->where = implode(' AND ', $where);
		
		$list = $manager->getList($relation['to'], $criteria);
		
		// prepare the result
		$result = null;
		if (!$sourceIsArray) {
			if ($list)
				$result = 'one' == $type ? current($list) : $list;
		}
		else {
			$result = array();
			foreach ($list as $related) {
				$key = array();
				
				foreach ($on as $l=>$r) {
					$name = $r['name'];
					$rValue = !isset($r['getter']) ? $related->$name : call_user_func(array($object, $r['getter']));
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
}
