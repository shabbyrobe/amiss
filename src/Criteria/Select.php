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

	public function buildQuery($meta)
	{
		$table = $meta->table;
		
		list ($where, $params) = $this->buildClause();
		$order = $this->buildOrder($meta);
		list ($limit, $offset) = $this->getLimitOffset();
		
		$query = "SELECT ".$this->buildFields($meta)." FROM $table "
			.($where  ? "WHERE $where "            : '').' '
			.($order  ? "ORDER BY $order "         : '').' '
			.($limit  ? "LIMIT  ".(int)$limit." "  : '').' '
			.($offset ? "OFFSET ".(int)$offset." " : '').' '
		;
		
		return array($query, $params);
	}
	
	protected function buildFields($meta)
	{
		$fields = '*';
		
		if (!$this->fields) {
			$metaFields = $meta->getFields();
			if ($metaFields) {
				$fields = array();
				foreach ($metaFields as $field) {
					$fields[] = $field['name'];
				}
				$fields = implode(', ', $fields);
			}
		}
		else {
			$fields = is_array($this->fields) ? implode(', ', $this->fields) : $this->fields;
		}
		
		return $fields;
	}
	
	protected function buildOrder($meta)
	{
		$order = array();
		if (is_string($this->order)) {
			return $this->order;
		}
		else {
			$fields = $meta->getFields();
			foreach ($this->order as $field=>$dir) {
				if (is_numeric($field)) { 
					$field = $dir; $dir = 'asc';
				}
				
				if (!isset($fields[$field]))
					throw new \Amiss\Exception("Attempted to order by nonexistent property $field for class {$meta->class}");
				
				$fieldMeta = $fields[$field];
				$field = $fieldMeta['name'];
				
				$dir = trim(strtolower($dir));
				$order[] = '`'.str_replace('`', '', $field).'`'.($dir == 'asc' ? '' : ' desc');
			}
			return implode(', ', $order);
		}
	}
}
