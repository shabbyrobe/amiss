<?php

namespace Amiss\Test\Unit;

class CookbookNestedObjectUsingTypeTest extends \CustomTestCase
{
	public function setUp()
	{
		$this->db = new \PDO('sqlite::memory:', null, null, array(\PDO::ATTR_ERRMODE=>\PDO::ERRMODE_EXCEPTION));
		$this->db->exec("CREATE TABLE cookbook_nested_object(id INTEGER PRIMARY KEY AUTOINCREMENT, thing_part1 STRING, thing_part2 STRING)");
		$this->db->exec("INSERT INTO cookbook_nested_object VALUES(1, 'abc', 'def')");
		
		$this->mapper = new \Amiss\Mapper\Note;
		$this->mapper->objectNamespace = __NAMESPACE__;
		$this->mapper->typeHandlers['pants'] = new CookbookNestedObjectTypeHandler();
		$this->manager = new \Amiss\Manager($this->db, $this->mapper);
	}
	
	/**
	 * This doesn't work at the moment because the field list that gets built from
	 * the metadata doesn't take the type handler into account, i.e. thing_part2 is
	 * never selected.
	 * 
	 * @group cookbook
	 * @group faulty
	 */
	public function testRetrieve()
	{
		$obj = $this->manager->getByPk('CookbookNestedObject', 1);
		var_dump($obj);
	}
}

class CookbookNestedObjectTypeHandler implements \Amiss\Type\Handler
{
	function prepareValueForDb($value, $object, array $fieldInfo)
	{
		$name = substr($fieldInfo['name'], 0, -6);
		return array(
			$name.'_part1'=>$value ? $value->part1 : null,
			$name.'_part2'=>$value ? $value->part2 : null,
		);
	}
	
	function handleValueFromDb($value, $object, array $fieldInfo, $row)
	{
		$name = substr($fieldInfo['name'], 0, -6);
		return (object)array(
			'part1'=>$row[$name.'_part1'],
			'part2'=>$row[$name.'_part2'],
		);
	}
	
	function createColumnType($engine) {}
}

class CookbookNestedObject
{
	/**
	 * @primary
	 * @type autoinc
	 */
	public $id;
	
	/**
	 * @field thing_part1
	 * @type pants
	 */
	public $thing;
}
