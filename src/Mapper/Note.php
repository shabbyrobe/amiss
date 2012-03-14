<?php

namespace Amiss\Mapper;

use Amiss\Exception;

class Note extends \Amiss\Mapper
{
	private $cache;
	
	public function __construct($cache=null)
	{
		$this->parser = new \Amiss\Note\Parser;
		$this->setCache($cache);
	}
	
	protected function setCache($cache)
	{
		if (is_object($cache)) {
			$cache = array(
				function($key) use ($cache) { return $cache->get($key); },
				function($key, $value) use ($cache) { return $cache->set($key, $value); },
			);
		}
		elseif ($cache == 'apc') {
			$cache = array(
				function($key) use ($cache) { return apc_fetch($key); },
				function($key, $value) use ($cache) { return apc_store($key, $value); },
			);
		}
		$this->cache = $cache;
	}
	
	public function getMeta($class)
	{
		$meta = null;
		
		$class = $this->resolveObjectName($class);
		
		if ($this->cache) {
			$meta = $this->cache[0]($class);
		}
		
		if (!$meta) {
			$ref = new \ReflectionClass($class);
			$notes = $this->parser->parseClass($ref);
			$classNotes = $notes->notes;
			$table = isset($classNotes['table']) ? $classNotes['table'] : $this->getDefaultTable($class);
			
			$parentClass = get_parent_class($class);
			$parent = null;
			if ($parentClass) {
				$parent = $this->getMeta($parentClass);
			}
			
			$info = array(
				'primary'=>null,
				'fields'=>array(),
				'relations'=>array(),
				'defaultFieldType'=>isset($classNotes['fieldType']) ? $classNotes['fieldType'] : null,
			);
			
			$setters = array();
			$priFound = false;
			
			$relationNotes = array();
			
			foreach (array('property'=>$notes->properties, 'method'=>$notes->methods) as $type=>$noteBag) {
				foreach ($noteBag as $name=>$itemNotes) {
					$field = null;
					$relationNote = null;
					
					if (isset($itemNotes['field']))
						$field = $itemNotes['field'] !== true ? $itemNotes['field'] : false;
					
					if (isset($itemNotes['has']))
						$relationNote = $itemNotes['has'];
					
					if (isset($itemNotes['primary'])) {
						if ($priFound)
							throw new \UnexpectedValueException("Found two primaries for $class");
						
						$info['primary'] = $name;
						$priFound = true;
						if (!$field) $field = $name;
					}
					
					if ($field !== null) {
						$fieldInfo = array();
						
						if ($type == 'method') {
							list($name, $fieldInfo['getter'], $fieldInfo['setter']) = $this->findGetterSetter($name, $itemNotes); 
						}
						
						$fieldInfo['name'] = $field;
						$fieldInfo['type'] = isset($itemNotes['type']) 
							? $itemNotes['type'] 
							: null
						;
						
						$info['fields'][$name] = $fieldInfo;
					}
					
					if ($relationNote !== null) {
						if ($field)
							throw new \UnexpectedValueException("Invalid class {$class}: relation and a field declared together on {$name}");
						
						$relationNotes[$name] = $itemNotes;
					}
				}
			}
			
			if ($relationNotes) {
				$info['relations'] = $this->buildRelations($relationNotes);
			}
			
			$info['fields'] = $this->resolveUnnamedFields($info['fields']);
			
			$meta = new \Amiss\Meta($class, $table, $info, $parent);
			
			if ($this->cache) {
				$this->cache[1]($class, $meta);
			}
		}
		return $meta;
	}
	
	protected function findGetterSetter($name, $itemNotes)
	{
		$getter = $name;
		$methodWithoutPrefix = $name[0] == 'g' && $name[1] == 'e' && $name[2] == 't' ? substr($name, 3) : $name;
		$name = lcfirst($methodWithoutPrefix);
		$setter = !isset($itemNotes['setter']) ? 'set'.$methodWithoutPrefix : $itemNotes['setter'];
		
		return array($name, $getter, $setter);
	}
	
	protected function buildRelations($relationNotes)
	{
		$relations = array();
		foreach ($relationNotes as $name=>$info) {
			$relationNote = preg_split('/\s+/', $info['has'], 3, PREG_SPLIT_NO_EMPTY);
			$relation = array(
				$relationNote[0]=>$relationNote[1],
				'on'=>null,
			);
			
			if (isset($relationNote[2])) {
				$relation['on'] = array();
				parse_str($relationNote[2], $on);
				foreach ($on as $k=>$v) {
					if (!$v) $v = $k;
					$relation['on'][$k] = $v;
				}
			}
			
			if (isset($info['getter'])) { 
				list($name, $relation['getter'], $relation['setter']) = $this->findGetterSetter($name, $info);
			}
			$relations[$name] = $relation;
		}
		return $relations;
	}
}
