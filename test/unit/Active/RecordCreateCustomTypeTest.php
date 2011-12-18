<?php

namespace Amiss\Test\Unit\Active;

use Amiss\Active\TableBuilder;

class RecordCreateCustomTypeTest extends \CustomTestCase
{
	public function setUp()
	{
		\Amiss\Active\Record::_reset();
		$this->connector = new \TestConnector('mysql:xx');
		$this->manager = new \Amiss\Manager($this->connector);
		\Amiss\Active\Record::setManager($this->manager);
		$this->tableBuilder = new TableBuilder(__NAMESPACE__.'\TestCreateActiveRecordWithCustomType');
	}
	
	/**
	 * @covers Amiss\Active\TableBuilder::buildFields
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
	 * @covers Amiss\Active\TableBuilder::buildFields
	 */
	public function testCreateTableWithCustomTypeUsesTypeHandler()
	{
		\Amiss\Active\Record::addTypeHandler(new TestCreateActiveRecordWithCustomTypeTypeHandler, 'slappywag');
		
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

class TestCreateActiveRecordWithCustomTypeTypeHandler implements \Amiss\Active\TypeHandler
{
	function prepareValueForDb($value)
	{
		return $value;
	}
	
	function handleValueFromDb($value)
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
