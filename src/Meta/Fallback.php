<?php

namespace Amiss\Meta;

class Fallback extends \Amiss\Meta
{
	private $table;
	

	public function getRowValues($obj)
	{
		$values = array();
		$manager = $this->getManager();
		
		$data = (array)$obj;
		$names = null;
		if ($manager->propertyColumnMapper)
			$names = $manager->propertyColumnMapper->to(array_keys($data));
		
		foreach ($obj as $k=>$v) {
			if ($names && isset($names[$k])) {
				$k = $names[$k];
			}
			elseif ($this->convertFieldUnderscores) {
				$k = trim(preg_replace_callback('/[A-Z]/', function($match) {
						return '_'.strtolower($match[0]);
				}, $k), '_');
			}
			if (!is_array($v) && !is_object($v) && !is_resource($v) && ($this->dontSkipNulls || $v !== null)) {
				$values[$k] = $v;
			}
		}
		
		return $values;
	}
}
