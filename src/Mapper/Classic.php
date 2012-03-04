<?php

namespace Amiss\Mapper;

class Classic extends \Amiss\Mapper
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
	
	public $objectNamespace=null;
	
	/**
	 * Whether or not Amiss should skip properties with a null value
	 * when converting an object to a row.
	 * 
	 * @var bool
	 */
	public $dontSkipNulls=false;

	public function resolveObjectName($name)
	{
		return ($this->objectNamespace && strpos($name, '\\')===false ? $this->objectNamespace . '\\' : '').$name;
	}
	
	public function getMeta($class)
	{
		
	}
	
	public function getRowValues($object)
	{
		
	}
	
	protected function getTable()
	{
		if (!$this->table) {
			$class = ltrim($this->class, '\\');
			$manager = $this->getManager();
			
			if (isset($manager->tableMap[$class])) {
				$table = $manager->tableMap[$class];
			}
			else {
				if (isset($manager->objectToTableMapper)) {
					if ($manager->objectToTableMapper instanceof Name\Mapper) {
						$result = $manager->objectToTableMapper->to(array($class));
						$table = current($result);
					}
					else {
						$table = call_user_func($manager->objectToTableMapper, $class);
					}
				}
				else {
					$table = $class;
					
					if ($pos = strrpos($table, '\\')) $table = substr($table, $pos+1);
					
					if ($manager->convertTableNames) {
						$table = trim(preg_replace_callback('/[A-Z]/', function($match) {
							return "_".strtolower($match[0]);
						}, $table), '_');
					}
				}
			}
			
			$table = str_replace('`', '', $table);
			
			$this->table = $table;
		}
		return $this->table;
	}
}
