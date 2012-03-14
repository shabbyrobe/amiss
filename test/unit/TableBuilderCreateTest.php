<?php

namespace Amiss\Test\Unit\Active;

use Amiss\TableBuilder;

class TableBuilderCreateTest extends \CustomTestCase
{
	public function setUp()
	{
		\Amiss\Active\Record::_reset();
		$this->manager = new \Amiss\Manager(
			new \Amiss\Connector('sqlite::memory:'),
			new \Amiss\Active\Mapper
		);
	}
	
	/**
	 * @group active
	 * @group tablebuilder
	 * @covers Amiss\TableBuilder::createTable
	 */
	public function testCreateDefaultTableSql()
	{
		\Amiss\Active\Record::setManager($this->manager);
		
		$tableBuilder = new TableBuilder($this->manager, __NAMESPACE__.'\TestCreateActiveRecord');
		 
		$pattern = "
			CREATE TABLE `test_create_active_record` (
				`testCreateActiveRecordId` int,
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
	 * @group active
	 * @group tablebuilder
	 */
	public function testBuildCreateFieldsDefault()
	{
		\Amiss\Active\Record::setManager($this->manager);
		
		$tableBuilder = new TableBuilder($this->manager, __NAMESPACE__.'\TestCreateActiveDefaultFieldRecord');
		
		$pattern = "
			CREATE TABLE `test_create_active_default_field_record` (
				`testCreateActiveDefaultFieldRecordId` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
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
	 * @group active
	 * @group tablebuilder
	 */
	public function testCreateTableWithSingleOnRelation()
	{
		\Amiss\Active\Record::setManager($this->manager);
		
		$tableBuilder = new TableBuilder($this->manager, __NAMESPACE__.'\TestCreateActiveRecordWithIndexedSingleOnRelation');
		
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
	 * @group active
	 * @group tablebuilder
	 */
	public function testCreateTableWithSingleOnRelationSkipsIndexesForSqlite()
	{
		\Amiss\Active\Record::setManager($this->manager);
		
		$tableBuilder = new TableBuilder($this->manager, __NAMESPACE__.'\TestCreateActiveRecordWithIndexedSingleOnRelation');
		
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
	 * @group active
	 * @group tablebuilder
	 */
	public function testCreateTableWithMultiOnRelation()
	{
		\Amiss\Active\Record::setManager($this->manager);
		
		$tableBuilder = new TableBuilder($this->manager, __NAMESPACE__.'\TestCreateActiveRecordWithIndexedMultiOnRelation');
		
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
	 * @group active
	 * @group tablebuilder
	 * @expectedException Amiss\Exception
	 */
	public function testCreateTableFailsWhenFieldsNotDefined()
	{
		\Amiss\Active\Record::setManager($this->manager);
		$tableBuilder = new TableBuilder($this->manager, __NAMESPACE__.'\TestNoFieldsCreateActiveRecord');
		$tableBuilder->createTable();
	}
	
	/**
	 * @group tablebuilder
	 * @group active
	 * @expectedException Amiss\Exception
	 */
	public function testCreateTableFailsWhenConnectorIsNotAmissConnector()
	{
		\Amiss\Active\Record::setManager($this->manager);
		$this->manager->connector = new \PDO('sqlite::memory:');
		$tableBuilder = new TableBuilder($this->manager, __NAMESPACE__.'\TestNoFieldsCreateActiveRecord');
		$tableBuilder->createTable();
	}
}

class TestNoFieldsCreateActiveRecord extends \Amiss\Active\Record
{
	
}

class TestCreateActiveRecord extends \Amiss\Active\Record
{
	public static $fields = array(
		'testCreateActiveRecordId'=>'int',
		'foo1'=>'varchar(128)',
		'foo2'=>'varchar(128)',
		'pants'=>'int unsigned not null',
	);
}

class TestCreateActiveDefaultFieldRecord extends \Amiss\Active\Record
{
	public static $fields = array(
		'foo',
		'bar',
	);
}

class TestCreateActiveRecordWithIndexedSingleOnRelation extends \Amiss\Active\Record
{
	public static $table = 'bar';
	public static $primary = 'barId';
	public static $fields = array(
		'fooId',
		'quack',
	);
	
	public static $relations = array(
		'foo'=>array('one'=>'FooRecord', 'on'=>'fooId'),
	);
}

class TestCreateActiveRecordWithIndexedMultiOnRelation extends \Amiss\Active\Record
{
	public static $table = 'bar';
	public static $primary = 'barId';
	public static $fields = array(
		'myFooId',
		'myOtherFooId',
		'bar',
	);
	
	public static $relations = array(
		'foo'=>array('one'=>'FooRecord', 'on'=>array('myFooId'=>'fooId', 'myOtherFooId'=>'otherFooId')),
	);
}
