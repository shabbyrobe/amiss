<?php

namespace Amiss\Mapper;

use Amiss\Exception;

class Note extends \Amiss\Mapper
{
	const CACHE_CLOSURE = 1;
	const CACHE_OBJ = 2;
	
	private $cache;
	private $cacheType;
	
	/**
	 * @param mixed $cache Any object that has two methods with the following
	 * signatures:
	 *     get($key)
	 *     set($key, $value)
	 * 
	 * OR a 2-tuple of functions with the same signatures:
	 *     array(function get($value) {}, function set($value, $key) {})
	 * 
	 * If it's null, it will try and use APC with a TTL of 3600
	 */
	public function __construct($cache=null)
	{
		if (!$cache) {
			$this->cacheType = self::CACHE_CLOSURE;
			$this->cache = array(
				function ($key) { return apc_fetch($key); },
				function ($key, $value) { if (!apc_store($key, $value, 3600)) { throw new \RuntimeException("Could not save into APC"); } }
			); 
		}
		else {
			$this->cache = $cache;
			$this->cacheType = is_array($cache) ? self::CACHE_CLOSURE : self::CACHE_OBJ;
		}
		
		$this->parser = new \Amiss\Note\Parser;
	}
	
	function exportRow($obj)
	{
		$fields = $this->getFields($obj);
		$values = array();
		
		foreach ($fields as $k=>$v) {
			$values[$v] = $obj->$k;
		}
		
		return $values;
	}
	
	protected function getFields($class)
	{
		$classNotes = $this->getClassNotes($class);
		
		$propFields = array();
		
		foreach ($classNotes->properties as $p=>$notes) {
			if (isset($notes['field'])) {
				$propFields[$p] = $notes['field'] && $notes['field'] !== true 
					? $notes['field'] 
					: $p
				;
			}
		}
		
		return $propFields;
	}
	
	protected function getClassNotes($class)
	{
		if (!$class instanceof \ReflectionClass) {
			$class = new \ReflectionClass($class);
		}
		return $this->parser->parseClass($class);
	}
	
	protected function populateObject($obj, $row)
	{
		$fields = $this->getFields($obj);
		foreach ($fields as $prop=>$col) {
			$obj->$prop = $row[$col];
		}
	}
	
	protected function getTable($class)
	{
		$notes = $this->getClassNotes($class);
		if (isset($notes->notes['table']))
			return $notes->notes['table'];
		else {
			$table = $class;
			
			if ($pos = strrpos($table, '\\')) $table = substr($table, $pos+1);
			
			$table = trim(preg_replace_callback('/[A-Z]/', function($match) {
				return "_".strtolower($match[0]);
			}, $table), '_');

			return $table;
		}
	}
}
