<?php

namespace Amiss\Criteria;

class Select extends Query
{
	public $args=array();
	public $page;
	public $limit;
	public $offset=0;
	public $fields;
	public $order=array();
	
	public function getLimitOffset()
	{
		if ($this->limit) 
			return array($this->limit, $this->offset);
		else {
			return array($this->page[1], ($this->page[0] - 1) * $this->page[1]); 
		}
	}
	
	public function buildFields()
	{
		$fields = null;
		if (!$this->fields) {
			$fields = '*';
		}
		else {
			$fields = is_array($this->fields) ? implode(', ', $this->fields) : $this->fields;
		}
		return $fields;
	}
	
	public function buildQuery($table)
	{
		list ($where, $params) = $this->buildClause();
		$order = $this->buildOrder();
		list ($limit, $offset) = $this->getLimitOffset();
		
		$query = "SELECT ".$this->buildFields()." FROM $table "
			.($where  ? "WHERE $where "            : '').' '
			.($order  ? "ORDER BY $order "         : '').' '
			.($limit  ? "LIMIT  ".(int)$limit." "  : '').' '
			.($offset ? "OFFSET ".(int)$offset." " : '').' '
		;
		
		return array($query, $params);
	}
	
	public function buildOrder()
	{
		$order = array();
		if (is_string($this->order)) {
			return $this->order;
		}
		else {
			foreach ($this->order as $field=>$dir) {
				if (is_numeric($field)) { 
					$field = $dir; $dir = 'asc';
				}
				$dir = trim(strtolower($dir));
				$order[] = '`'.str_replace('`', '', $field).'`'.($dir == 'asc' ? '' : ' desc');
			}
			return implode(', ', $order);
		}
	}
}
