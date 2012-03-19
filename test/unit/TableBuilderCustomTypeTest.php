<?php

namespace Amiss\Test\Unit;

use Amiss\TableBuilder;

class TableBuilderCustomTypeTest extends \CustomTestCase
{
	public function setUp()
	{
		$this->connector = new \TestConnector('mysql:xx');
		$this->mapper = new \Amiss\Mapper\Statics();
		$this->manager = new \Amiss\Manager($this->connector, $this->mapper);
		$this->tableBuilder = new TableBuilder($this->manager, __NAMESPACE__.'\TestCreateWithCustomType');
	}
	
	/**
	 * @covers Amiss\TableBuilder::buildFields
	 * @group tablebuilder
	 * @group unit
	 */
	public function testCreateTableWithCustomTypeUsesRubbishValueWhenTypeHandlerNotRegistered()
	{
		$pattern = "
			CREATE TABLE `bar` (
				`testCreateId` INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT,
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
	 * @group tablebuilder
	 * @group unit
	 */
	public function testCreateTableWithCustomTypeUsesTypeHandler()
	{
		$this->mapper->addTypeHandler(new TestCreateWithCustomTypeTypeHandler, 'slappywag');
		
		$pattern = "
			CREATE TABLE `bar` (
				`testCreateId` INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT,
				`foo1` OH YEAH,
				`foo2` OH YEAH,
				`pants` int unsigned not null
			) ENGINE=InnoDB
		";
		$this->tableBuilder->createTable();
		
		$this->assertLoose($pattern, $this->connector->getLastCall());
	}
}

class TestCreateWithCustomType
{
	public static $table = 'bar';
	public static $primary = 'testCreateId';
	
	public static $fields = array(
		'foo1'=>'slappywag',
		'foo2'=>'slappywag',
		'pants'=>'int unsigned not null',
	);
}

class TestCreateWithCustomTypeTypeHandler implements \Amiss\Type\Handler
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
