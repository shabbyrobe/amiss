<?php

/*
 * This file is part of Amiss.
 * 
 * Amiss is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * Amiss is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with Amiss.  If not, see <http://www.gnu.org/licenses/>.
 * 
 * Copyright 2011 Blake Williams
 * http://k3jw.com 
 */

namespace Amiss\Test\Unit;

use Amiss\Active\TableBuilder;

class ActiveRecordCreateTest extends \CustomTestCase
{
	public function setUp()
	{
		\Amiss\Active\Record::_reset();
		$this->manager = new \Amiss\Manager(new \Amiss\Connector('sqlite::memory:'));
	}
	
	public function testCreateDefaultTableSql()
	{
		\Amiss\Active\Record::setManager($this->manager);
		
		$tableBuilder = new TableBuilder(__NAMESPACE__.'\TestCreateActiveRecord');
		 
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
	
	public function testBuildCreateFieldsDefault()
	{
		\Amiss\Active\Record::setManager($this->manager);
		
		$tableBuilder = new TableBuilder(__NAMESPACE__.'\TestCreateActiveDefaultFieldRecord');
		
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

	public function testCreateTableWithSingleOnRelation()
	{
		\Amiss\Active\Record::setManager($this->manager);
		
		$tableBuilder = new TableBuilder(__NAMESPACE__.'\TestCreateActiveRecordWithIndexedSingleOnRelation');
		
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

	public function testCreateTableWithSingleOnRelationSkipsIndexesForSqlite()
	{
		\Amiss\Active\Record::setManager($this->manager);
		
		$tableBuilder = new TableBuilder(__NAMESPACE__.'\TestCreateActiveRecordWithIndexedSingleOnRelation');
		
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
	
	public function testCreateTableWithMultiOnRelation()
	{
		\Amiss\Active\Record::setManager($this->manager);
		
		$tableBuilder = new TableBuilder(__NAMESPACE__.'\TestCreateActiveRecordWithIndexedMultiOnRelation');
		
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
	 * @expectedException Amiss\Exception
	 */
	public function testCreateTableFailsWhenFieldsNotDefined()
	{
		\Amiss\Active\Record::setManager($this->manager);
		$tableBuilder = new TableBuilder(__NAMESPACE__.'\TestNoFieldsCreateActiveRecord');
		$tableBuilder->createTable();
	}
	
	/**
	 * @expectedException Amiss\Exception
	 */
	public function testCreateTableFailsWhenConnectorIsNotAmissConnector()
	{
		\Amiss\Active\Record::setManager($this->manager);
		$this->manager->connector = new \PDO('sqlite::memory:');
		$tableBuilder = new TableBuilder(__NAMESPACE__.'\TestNoFieldsCreateActiveRecord');
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
