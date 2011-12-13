<?php

namespace Amiss\Criteria;

class Update extends Query
{
	public $set=array();
	
	public function buildSet()
	{
		$params = array();
		$clause = array();
		if ($this->paramsAreNamed()) {
			foreach ($this->set as $k=>$v) {
				if (is_numeric($k)) {
					$clause[] = $v;
				}
				else {
					$param = ':set_'.$k;
					$clause[] = '`'.$k.'`='.$param;
					$params[$param] = $v;
				}
			}
		}
		else {
			foreach ($this->set as $k=>$v) {
				if (is_numeric($k)) {
					$clause[] = $v;
				}
				else {
					$clause[] = '`'.$k.'`=?';
					$params[] = $v;
				}
				
			}
		}
		
		$clause = implode(', ', $clause);
		
		return array($clause, $params);
	}
}
