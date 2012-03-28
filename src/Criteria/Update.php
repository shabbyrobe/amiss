<?php

namespace Amiss\Criteria;

class Update extends Query
{
	public $set=array();
	
	public function buildSet($meta)
	{
		$params = array();
		$clause = array();
		
		$fields = $meta ? $meta->getFields() : null;
		$named = $this->paramsAreNamed();
		
		foreach ($this->set as $name=>$value) {
			if (is_numeric($name)) {
				// this allows arrays of manual "set"s, i.e. array('foo=foo+10', 'bar=baz')
				$clause[] = $value;
			}
			else {
				$field = (isset($fields[$name]) ? $fields[$name]['name'] : $name);
				
				if ($named) {
					$param = ':set_'.$name;
					$clause[] = '`'.$field.'`='.$param;
					$params[$param] = $value;
				}
				else {
					$clause[] = '`'.$field.'`=?';
					$params[] = $value;
				}
			}
		}
		
		$clause = implode(', ', $clause);
		
		return array($clause, $params);
	}
}
