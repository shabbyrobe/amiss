<?php

namespace Amiss\Mapper;

use Amiss\RowBuilder,
	Amiss\RowExporter
;

class Quick extends \Amiss\Mapper
{
	public $tableMap=array();
	
	/**
	 * Translator for object names to table names.
	 * 
	 * If an ``Amiss\Name\Mapper`` is used, only the ``to()`` method will be used.
	 * 
	 * @var mixed callable or Amiss\Name\Mapper
	 */
	public $objectToTableMapper=null;
	
	/**
	 * Translator for property names to column names.
	 * 
	 * Should implement ``to()`` to turn a property name into a column name,
	 * and ``from()`` to turn a column name into a property name 
	 * 
	 * @var Amiss\Name\Mapper
	 */
	public $propertyColumnMapper=null;
	
	public $convertTableNames=true;
	
	public $convertFieldUnderscores=false;
	
	/**
	 * Whether or not Amiss should skip properties with a null value
	 * when converting an object to a row.
	 * 
	 * @var bool
	 */
	public $dontSkipNulls=false;

	public $objectNamespace=null;

	public function resolveObjectName($name)
	{
		return (($this->objectNamespace && strpos($name, '\\')===false) ? $this->objectNamespace . '\\' : '').$name;
	}
	
	protected function getRowValues($obj)
	{
		$values = array();
		
		$data = (array)$obj;
		$names = null;
		if ($this->propertyColumnMapper)
			$names = $this->propertyColumnMapper->to(array_keys($data));
		
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
	
	public function exportRow($obj)
	{
		if ($obj instanceof RowExporter) {
			$values = $obj->exportRow();
			if (!is_array($values)) {
				throw new Exception("Row exporter must return an array!");
			}
		}
		else {
			$values = $this->getRowValues($obj);
		}
		
		return $values;
	}
	
	public function createObject($row, $className, $args=null)
	{
		$fqcn = $this->resolveObjectName($className);
		
		$class = new $fqcn;

		if ($class instanceof RowBuilder) {
			$class->buildObject($row);
		}
		else {
			$names = null;
			if (isset($this->propertyColumnMapper)) {
				$names = $this->propertyColumnMapper->from(array_keys($row));
			}
			foreach ($row as $k=>$v) {
				if ($names && isset($names[$k])) {
					$prop = $names[$k];
				}
				else {
					if ($this->convertFieldUnderscores) {
						$prop = trim(preg_replace_callback('/_(.)/', function($match) {
							return strtoupper($match[1]);
						}, $k), '_');
					}
					else {
						$prop = $k;
					}
				}
				$class->$prop = $v;
			}
		}
		return $class;
	}
	
	protected function getTable($class)
	{
		$class = ltrim($class, '\\');

		if (isset($this->tableMap[$class])) {
			$table = $this->tableMap[$class];
		}
		else {
			if (isset($this->objectToTableMapper)) {
				if ($this->objectToTableMapper instanceof Name\Mapper) {
					$result = $this->objectToTableMapper->to(array($class));
					$table = current($result);
				}
				else {
					$table = call_user_func($this->objectToTableMapper, $class);
				}
			}
			else {
				$table = $class;
				
				if ($pos = strrpos($table, '\\')) $table = substr($table, $pos+1);
				
				if ($this->convertTableNames) {
					$table = trim(preg_replace_callback('/[A-Z]/', function($match) {
						return "_".strtolower($match[0]);
					}, $table), '_');
				}
			}
		}
		
		$table = str_replace('`', '', $table);
		
		return $table;
	}
}
