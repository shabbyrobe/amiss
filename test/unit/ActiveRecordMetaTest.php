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

use Amiss\Active\TypeHandler;

use Amiss\Active\TableBuilder;

class ActiveRecordMetaTest extends \CustomTestCase
{
	public function testGetTypeHandlerWhenTypeContainsLotsOfExtraDbSpecificStuff()
	{
		$meta = $this->getFieldMeta(array(
			'field'=>'int foo bar',
		));
		$meta->typeHandlers['int'] = new MetaTestTypeHandler();
		$handler = $meta->getTypeHandler('int foo bar');
		
		$this->assertInstanceOf(__NAMESPACE__.'\MetaTestTypeHandler', $handler);
	}
	
	public function testGetTypeHandlerWhenTypeContainsBrackets()
	{
		$meta = $this->getFieldMeta(array(
			'field'=>'varchar(25)',
		));
		$meta->typeHandlers['varchar'] = new MetaTestTypeHandler();
		$handler = $meta->getTypeHandler('varchar(25)');
		
		$this->assertInstanceOf(__NAMESPACE__.'\MetaTestTypeHandler', $handler);
	}
	
	private function getFieldMeta($fields)
	{
		$meta = $this->getMock('Amiss\Active\Meta', array('getFields'), array(), '', false);
		$meta->expects($this->any())->method('getFields')->will($this->returnValue($fields));
		return $meta;
	}
}

class MetaTestTypeHandler implements TypeHandler
{
	function prepareValueForDb($value)
	{
		return 'db';
	}
	
	function handleValueFromDb($value)
	{
		return 'value';
	}
	
	function createColumnType($engine)
	{}
}
