<?php

namespace Amiss\Test\Unit\Active;

use Amiss\Active\TableBuilder;

class RecordCreateCustomTypeWithEmptyColumnTypeTest extends \CustomTestCase
{
	public function setUp()
	{
		\Amiss\Active\Record::_reset();
		$this->connector = new \TestConnector('mysql:xx');
		$this->manager = new \Amiss\Manager($this->connector);
		\Amiss\Active\Record::setManager($this->manager);
		$this->tableBuilder = new TableBuilder(__NAMESPACE__.'\TestCreateCustomTypeWithEmptyColumnTypeRecord');
	}
	
	/**
	 * @covers Amiss\Active\TableBuilder::buildFields
	 * @group active
	 */
	public function testCreateTableWithCustomTypeUsesTypeHandler()
	{
		\Amiss\Active\Record::addTypeHandler(new RecordCreateCustomTypeWithEmptyColumnTypeHandler, 'int');
		
		$pattern = "
			CREATE TABLE `bar` (
				`id` INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT,
				`foo1` int
			) ENGINE=InnoDB
		";
		$this->tableBuilder->createTable();
		
		$this->assertLoose($pattern, $this->connector->getLastCall());
	}
}

class TestCreateCustomTypeWithEmptyColumnTypeRecord extends \Amiss\Active\Record
{
	public static $table = 'bar';
	public static $primary = 'id';
	public static $fields = array(
		'foo1'=>'int',
	);
}

class RecordCreateCustomTypeWithEmptyColumnTypeHandler implements \Amiss\Type\Handler
{
	function prepareValueForDb($value, $object, $fieldName)
	{
		return $value;
	}
	
	function handleValueFromDb($value, $object, $fieldName)
	{
		return $value;
	}
	
	function createColumnType($engine)
	{}
}
