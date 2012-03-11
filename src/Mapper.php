<?php

namespace Amiss;

abstract class Mapper
{
	function resolveObjectName($name)
	{
		return $name;
	}
	
	abstract function exportRow($obj);

	function createObject($row, $className, $args=null)
	{
		$fqcn = $this->resolveObjectName($className);
		
		if ($args) {
			$class = new \ReflectionClass($fqcn);
			$obj = $class->newInstanceArgs($args);
		}
		else {
			$obj = new $fqcn;
		}

		$this->populateObject($obj, $row);

		return $obj;
	}
	
	abstract protected function populateObject($obj, $row);
	
	abstract protected function getTable($obj);

	public function getMeta($class, Meta $parent=null)
	{
		$class = $this->resolveObjectName($class);
		$table = $this->getTable($class);
		return new Meta($class, $table, $parent);
	}
}
