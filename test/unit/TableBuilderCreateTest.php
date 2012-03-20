<?php

namespace Amiss\Test\Unit;

use Amiss\TableBuilder;

class TableBuilderCreateTest extends \CustomTestCase
{
	public function setUp()
	{
		$this->manager = new \Amiss\Manager(
			new \Amiss\Connector('sqlite::memory:'),
			new \Amiss\Mapper\Statics
		);
	}
	
	/**
	 * @group tablebuilder
	 * @group unit
	 */
	public function testCreateDefaultTableSql()
	{
		$tableBuilder = new TableBuilder($this->manager, __NAMESPACE__.'\TestCreate');
		 
		$pattern = "
			CREATE TABLE `test_create` (
				`testCreateId` int,
				`foo1` varchar(128),
				`foo2` varchar(128),
				`pants` int unsigned not null
			)
		";
		
		$this->manager->connector = $this->getMock('Amiss\Connector', array('exec'), array('sqlite::memory:'));
		$this->manager->connector->expects($this->once())->method('exec')
			->with($this->matchesLoose($pattern));
		
		$tableBuilder->createTable();
	}
	
	/**
	 * @group tablebuilder
	 * @group unit
	 */
	public function testBuildCreateFieldsDefault()
	{
		$tableBuilder = new TableBuilder($this->manager, __NAMESPACE__.'\TestCreateDefaultField');
		
		$pattern = "
			CREATE TABLE `test_create_default_field` (
				`testCreateDefaultFieldId` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
				`foo` STRING null,
				`bar` STRING null
			)
		";
		$this->manager->connector = $this->getMock('Amiss\Connector', array('exec'), array('sqlite::memory:'));
		$this->manager->connector->expects($this->once())->method('exec')
			->with($this->matchesLoose($pattern));
		
		$tableBuilder->createTable();
	}

	/**
	 * @group tablebuilder
	 * @group unit
	 */
	public function testCreateTableWithSingleOnRelation()
	{
		$tableBuilder = new TableBuilder($this->manager, __NAMESPACE__.'\TestCreateWithIndexedSingleOnRelation');
		
		$pattern = "
			CREATE TABLE `bar` (
				`barId` INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT,
				`fooId` varchar(255) null,
				`quack` varchar(255) null,
				KEY `idx_foo` (`fooId`)
			)
		";
		$this->manager->connector = $this->getMock('Amiss\Connector', array('exec'), array('mysql:xx'));
		$this->manager->connector->expects($this->once())->method('exec')
			->with($this->matchesLoose($pattern));
		
		$tableBuilder->createTable();
	}

	/**
	 * @group tablebuilder
	 * @group unit
	 */
	public function testCreateTableWithSingleOnRelationSkipsIndexesForSqlite()
	{
		$tableBuilder = new TableBuilder($this->manager, __NAMESPACE__.'\TestCreateWithIndexedSingleOnRelation');
		
		$pattern = "
			CREATE TABLE `bar` (
				`barId` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
				`fooId` STRING null,
				`quack` STRING null
			)
		";
		$this->manager->connector = $this->getMock('Amiss\Connector', array('exec'), array('sqlite::memory:'));
		$this->manager->connector->expects($this->once())->method('exec')
			->with($this->matchesLoose($pattern));
		
		$tableBuilder->createTable();
	}

	/**
	 * @group tablebuilder
	 * @group unit
	 */
	public function testCreateTableWithMultiOnRelation()
	{
		$tableBuilder = new TableBuilder($this->manager, __NAMESPACE__.'\TestCreateWithIndexedMultiOnRelation');
		
		$pattern = "
			CREATE TABLE `bar` (
				`barId` INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT,
				`myFooId` varchar(255) null,
				`myOtherFooId` varchar(255) null,
				`bar` varchar(255) null,
				KEY `idx_foo` (`myFooId`, `myOtherFooId`)
			)
		";
		$this->manager->connector = $this->getMock('Amiss\Connector', array('exec'), array('mysql:xx'));
		$this->manager->connector->expects($this->once())->method('exec')
			->with($this->matchesLoose($pattern));
		
		$tableBuilder->createTable();
	}

	/**
	 * @group tablebuilder
	 * @group unit
	 * @expectedException Amiss\Exception
	 */
	public function testCreateTableFailsWhenFieldsNotDefined()
	{
		$tableBuilder = new TableBuilder($this->manager, __NAMESPACE__.'\TestNoFieldsCreate');
		$tableBuilder->createTable();
	}
	
	/**
	 * @group tablebuilder
	 * @group unit
	 * @expectedException Amiss\Exception
	 */
	public function testCreateTableFailsWhenConnectorIsNotAmissConnector()
	{
		$this->manager->connector = new \PDO('sqlite::memory:');
		$tableBuilder = new TableBuilder($this->manager, __NAMESPACE__.'\TestNoFieldsCreate');
		$tableBuilder->createTable();
	}
}

class TestNoFieldsCreate
{
	
}

class TestCreate
{
	public static $fields = array(
		'testCreateId'=>'int',
		'foo1'=>'varchar(128)',
		'foo2'=>'varchar(128)',
		'pants'=>'int unsigned not null',
	);
}

class TestCreateDefaultField
{
	public static $fields = array(
		'testCreateDefaultFieldId'=>'autoinc',
		'foo',
		'bar',
	);
}

class TestCreateWithIndexedSingleOnRelation
{
	public static $table = 'bar';
	public static $primary = 'barId';
	public static $fields = array(
		'barId'=>'autoinc',
		'fooId',
		'quack',
	);
	
	public static $relations = array(
		'foo'=>array('one'=>'FooRecord', 'on'=>'fooId'),
	);
}

class TestCreateWithIndexedMultiOnRelation
{
	public static $table = 'bar';
	public static $primary = 'barId';
	public static $fields = array(
		'barId'=>'autoinc',
		'myFooId',
		'myOtherFooId',
		'bar',
	);
	
	public static $relations = array(
		'foo'=>array('one'=>'FooRecord', 'on'=>array('myFooId'=>'fooId', 'myOtherFooId'=>'otherFooId')),
	);
}
