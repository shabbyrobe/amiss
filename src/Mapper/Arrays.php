<?php

namespace Amiss\Mapper;

class Arrays extends Base
{
	public $arrayMap;
	public $inherit = false;
	
	public function __construct($arrayMap=array())
	{
		parent::__construct();
		
		$this->arrayMap = $arrayMap;
	}
	
	protected function createMeta($class)
	{
		if (!isset($this->arrayMap[$class]))
			throw new \InvalidArgumentException("Unknown class $class");
		
		$array = $this->arrayMap[$class];
		$parent = null;
		if ($this->inherit) {
			$parent = $this->getMeta(get_parent_class($class));
		}
		
		$table = null;
		if (isset($array['table']))
			$table = $array['table'];
		else
			$table = $this->getDefaultTable($class);
		
		if (isset($array['fields'])) {
			foreach ($array['fields'] as $k=>&$v) {
				if (!isset($v['name'])) $v['name'] = $k;
				if (!isset($v['type'])) $v['type'] = null;
			}
		}
		
		$meta = new \Amiss\Meta($class, $table, $array, $parent);
		
		return $meta;
	}
}
