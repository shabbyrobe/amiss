<?php

namespace Amiss\Name;

class CamelToUnderscore implements Mapper
{
	public $strict = true;
	
	function to(array $names)
	{
		$trans = array();
		
		foreach ($names as $name) {
			if ($this->strict) {
				$pos = strpos($name, '_');
				if ($pos !== 0) {
					throw new \InvalidArgumentException("Property $name contains underscores - this will not successfully map bi-directionally. If you insist on using this name, your type should implement RowBuilder and RowExporter");
				}
			}
			
			$trans[$name] = trim(
				strtolower(preg_replace_callback(
					'/[A-Z]/', 
					function($match) {
						return '_'.$match[0];
					}, 
					$name
				)), 
				'_'
			);
		}
		
		return $trans;
	}
	
	function from(array $names)
	{
		$trans = array();
		
		foreach ($names as $name) {
			$trans[$name] = trim(
				preg_replace_callback(
					'/_(.)/', 
					function($match) {
						return strtoupper($match[1]);
					}, 
					$name
				), 
				'_'
			);
		}
		
		return $trans;
	} 
}
