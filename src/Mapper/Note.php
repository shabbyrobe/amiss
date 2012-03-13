<?php

namespace Amiss\Mapper;

use Amiss\Exception;

class Note extends \Amiss\Mapper
{
	public $propertyColumnTranslator;
	
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
			
			$unnamed = array();
			$setters = array();
			$priFound = false;
			
			foreach (array('property'=>$notes->properties, 'method'=>$notes->methods) as $type=>$noteBag) {
				foreach ($noteBag as $name=>$itemNotes) {
					$field = null;
					
					if (isset($itemNotes['field']))
						$field = $itemNotes['field'] !== true ? $itemNotes['field'] : false;
					
					if (isset($itemNotes['primary'])) {
						if ($priFound)
							throw new \UnexpectedValueException("Found two primaries for $class");
						
						$info['primary'] = $name;
						$priFound = true;
						if (!$field) $field = $name;
					}
					
					if ($field !== null) {
						$getSet = null;
						$methodWithoutPrefix = null;
						
						if ($type == 'method') {
							$getSet = array($name);
							$methodWithoutPrefix = $name[0] == 'g' && $name[1] == 'e' && $name[2] == 't' ? substr($name, 3) : $name;
							$name = lcfirst($methodWithoutPrefix);
							$getSet[] = !isset($itemNotes['setter']) ? 'set'.$methodWithoutPrefix : $itemNotes['setter']; 
						}
						
						if ($field === false) {
							$unnamed[$name] = $name;
						}
						
						$type = isset($itemNotes['fieldType']) 
							? $itemNotes['fieldType'] 
							: null
						;
						
						$info['fields'][$name] = array($field, $type, $getSet);
					}
				}
			}
			
			if ($unnamed) {
				if ($this->propertyColumnTranslator)
					$unnamed = $this->propertyColumnTranslator->to($unnamed);
				
				foreach ($unnamed as $prop=>$field) {
					$info['fields'][$prop][0] = $field;
				}
			}
			
			$meta = new \Amiss\Meta($class, $table, $info, $parent);
			
			if ($this->cache) {
				$this->cache[1]($class, $meta);
			}
		}
		return $meta;
	}
	
	protected function getDefaultTable($class)
	{
		$table = $class;
		
		if ($pos = strrpos($table, '\\')) $table = substr($table, $pos+1);
		
		$table = trim(preg_replace_callback('/[A-Z]/', function($match) {
			return "_".strtolower($match[0]);
		}, $table), '_');

		return $table;
	}
}
