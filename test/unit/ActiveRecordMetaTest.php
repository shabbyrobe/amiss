<?php

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
