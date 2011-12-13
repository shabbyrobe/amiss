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

class RecordCreateCustomTypeWithEmptyColumnTypeHandler implements \Amiss\Active\TypeHandler
{
	function prepareValueForDb($value)
	{
		return $value;
	}
	
	function handleValueFromDb($value)
	{
		return $value;
	}
	
	function createColumnType($engine)
	{}
}
