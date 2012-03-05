<?php

namespace Amiss;

abstract class Mapper
{
	abstract function resolveObjectName($name);
	
	abstract function exportRow($obj);

	abstract function createObject($row, $className, $args=null);
	
	abstract protected function getTable($obj);

	public function getMeta($class, Meta $parent=null)
	{
		$class = $this->resolveObjectName($class);
		$table = $this->getTable($class);
		return new Meta($class, $table, $parent);
	}
}
