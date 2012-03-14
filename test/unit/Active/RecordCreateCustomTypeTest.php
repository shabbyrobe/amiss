<?php

namespace Amiss\Test\Unit\Active;

use Amiss\TableBuilder;

class RecordCreateCustomTypeTest extends \CustomTestCase
{
	public function setUp()
	{
		\Amiss\Active\Record::_reset();
		$this->connector = new \TestConnector('mysql:xx');
		$this->mapper = new \Amiss\Active\Mapper();
		$this->manager = new \Amiss\Manager($this->connector, $this->mapper);
		\Amiss\Active\Record::setManager($this->manager);
		$this->tableBuilder = new TableBuilder($this->manager, __NAMESPACE__.'\TestCreateActiveRecordWithCustomType');
	}
	
	/**
	 * @covers Amiss\TableBuilder::buildFields
	 * @group active
	 * @group tablebuilder
	 */
	public function testCreateTableWithCustomTypeUsesRubbishValueWhenTypeHandlerNotRegistered()
	{
		$pattern = "
			CREATE TABLE `bar` (
				`testCreateActiveRecordId` INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT,
				`foo1` slappywag,
				`foo2` slappywag,
				`pants` int unsigned not null
			) ENGINE=InnoDB
		";
		$this->tableBuilder->createTable();
		$this->assertLoose($pattern, $this->connector->getLastCall());
	}
	
	/**
	 * @covers Amiss\TableBuilder::buildFields
	 * @group active
	 * @group tablebuilder
	 */
	public function testCreateTableWithCustomTypeUsesTypeHandler()
	{
		$this->mapper->addTypeHandler(new TestCreateActiveRecordWithCustomTypeTypeHandler, 'slappywag');
		
		$pattern = "
			CREATE TABLE `bar` (
				`testCreateActiveRecordId` INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT,
				`foo1` OH YEAH,
				`foo2` OH YEAH,
				`pants` int unsigned not null
			) ENGINE=InnoDB
		";
		$this->tableBuilder->createTable();
		
		$this->assertLoose($pattern, $this->connector->getLastCall());
	}
}

class TestCreateActiveRecordWithCustomType extends \Amiss\Active\Record
{
	public static $table = 'bar';
	public static $primary = 'testCreateActiveRecordId';
	
	public static $fields = array(
		'foo1'=>'slappywag',
		'foo2'=>'slappywag',
		'pants'=>'int unsigned not null',
	);
}

class TestCreateActiveRecordWithCustomTypeTypeHandler implements \Amiss\Type\Handler
{
	function prepareValueForDb($value, $object, $fieldName)
	{
		return $value;
	}
	
	function handleValueFromDb($value, $object, $fieldName)
	{
		return $value;
	}
	
	/**
	 * It's ok to return nothing from this - the default column type
	 * will be used.
	 */
	function createColumnType($engine)
	{
		return "OH YEAH";
	}
}
