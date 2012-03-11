<?php

namespace Amiss\Mapper;

use Amiss\Exception;

class Note implements \Amiss\Mapper
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
				'fields'=>array(),
				'relations'=>array(),
				'defaultFieldType'=>isset($classNotes['fieldType']) ? $classNotes['fieldType'] : null,
			);
			
			foreach ($notes->properties as $prop=>$propNotes) {
				$field = isset($propNotes['field']) && $propNotes['field'] !== true ? $propNotes['field'] : $prop;
				$type = isset($propNotes['fieldType']) 
					? $propNotes['fieldType'] 
					: (isset($propNotes['var']) ? $propNotes['var'] : null)
				;
				$info['fields'][$field] = $type;
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
