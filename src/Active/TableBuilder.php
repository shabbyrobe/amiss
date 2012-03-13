<?php

namespace Amiss\Active;

use Amiss\Exception,
	Amiss\Connector
;

class TableBuilder
{
	/**
	 * @var Amiss\Active\Meta
	 */
	private $meta;
	
	public function __construct($class)
	{
		$this->meta = $class::getMeta();
	}
	
	public function getClass()
	{
		return $this->meta->class;
	}
	
	public function createTable()
	{
		$connector = $this->meta->getManager()->getConnector();
		
		if (!($connector instanceof Connector))
			throw new Exception("Can't create tables if not using Amiss\Connector");
		
		$sql = $this->buildCreateTableSql();
		
		$connector->exec($sql);
	}
	
	protected function buildFields()
	{
		$manager = $this->meta->getManager();
		
		$engine = $manager->getConnector()->engine;
		$primary = $this->meta->getPrimary();
		
		if ($primary)
			$autoinc = $engine == 'sqlite' ? 'AUTOINCREMENT' : 'AUTO_INCREMENT';
		
		$primaryType = "INTEGER NOT NULL PRIMARY KEY $autoinc";
		$default = $this->meta->getDefaultFieldType();
		if (!$default) {
			$default = $engine == 'sqlite' ? 'STRING NULL' : 'VARCHAR(255) NULL';
		} 
		$f = array();
		$found = array();
		
		foreach ($this->meta->getFields() as $k=>$v) {
			if (is_numeric($k)) {
				if ($v != $primary)
					$f[] = "`$v` $default";
				else
					$f[] = "`$v` $primaryType";
				$found[] = $v;
			}
			else {
				$handler = $this->meta->getTypeHandler($v);
				if ($handler) {
					$new = $handler->createColumnType($engine);
					if ($new) $v = $new;
				}
				
				$f[] = "`$k` $v";
				$found[] = $k;
			}
		}
		if ($primary && !in_array($primary, $found)) {
			array_unshift($f, "`$primary` $primaryType");
		}
		return $f;
	}
	
	protected function buildTableConstraints()
	{
		$manager = $this->meta->getManager();
		$engine = $manager->getConnector()->engine;
		
		$idx = array();
		if ($engine == 'mysql') {
			foreach ($this->meta->getRelations() as $k=>$details) {
				$cols = array();
				if (is_string($details['on'])) {
					$cols[] = $details['on'];
				}
				else {
					foreach ($details['on'] as $l=>$r) {
						if (is_numeric($l)) $l = $r;
						$cols[] = $l;
					}
				}
				if (isset($details['one']))
					$idx[] = "KEY `idx_$k` (`".implode('`, `', $cols).'`)';
			}
		}
		return $idx;
	}
	
	public function buildCreateTableSql()
	{
		$fields = $this->meta->getFields();
		
		if (!$fields)
			throw new Exception("Can't create an object that doesn't declare fields");
		
		$table = '`'.str_replace('`', '', $this->meta->table).'`';
		$manager = $this->meta->getManager();
		$connector = $manager->getConnector();
		$engine = $connector->engine;
		
		$primary = $this->meta->primary;
		$fields = static::buildFields();
		if (is_array($fields))
			$fields = implode(",\n  ", $fields);
		
		$query = "CREATE TABLE $table (\n  ";
		$query .= $fields;
		
		$indexes = $this->buildTableConstraints();
		if ($indexes) {
			$query .= ",\n  ".implode(",\n  ", $indexes);
		}
		
		$query .= "\n)";
		if ($engine == 'mysql') {
			$query .= ' ENGINE=InnoDB';
		}
		
		return $query;
	}
}
