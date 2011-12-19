<?php

namespace Amiss\Test\Unit\Active;

use Amiss\Active\Meta;

use Amiss\Active\TypeHandler;

use Amiss\Active\TableBuilder;

class MetaTypeHandlerTest extends \CustomTestCase
{
	/**
	 * @covers Amiss\Active\Meta::getTypeHandler
	 */
	public function testGetTypeHandlerWhenTypeContainsLotsOfExtraDbSpecificStuff()
	{
		$meta = $this->getFieldMeta(array(
			'field'=>'int foo bar',
		));
		$meta->typeHandlers['int'] = new MetaTestTypeHandler(1);
		$handler = $meta->getTypeHandler('int foo bar');
		
		$this->assertInstanceOf(__NAMESPACE__.'\MetaTestTypeHandler', $handler);
	}
	
	/**
	 * @covers Amiss\Active\Meta::getTypeHandler
	 */
	public function testGetTypeHandlerWhenTypeContainsBrackets()
	{
		$meta = $this->getFieldMeta(array(
			'field'=>'varchar(25)',
		));
		$meta->typeHandlers['varchar'] = new MetaTestTypeHandler(1);
		$handler = $meta->getTypeHandler('varchar(25)');
		
		$this->assertInstanceOf(__NAMESPACE__.'\MetaTestTypeHandler', $handler);
	}
	
	/**
	 * @covers Amiss\Active\Meta::getTypeHandler
	 */
	public function testGetTypeHandlerFromActiveRecordMethod()
	{
		$meta = new Meta(__NAMESPACE__.'\MetaTestRecord');
		$handler = $meta->getTypeHandler('foo');
		$this->assertInstanceOf(__NAMESPACE__.'\MetaTestTypeHandler', $handler);
	}

	/**
	 * @covers Amiss\Active\Meta::getTypeHandler
	 */
	public function testGetUnknownTypeHandler()
	{
		$meta = new Meta(__NAMESPACE__.'\MetaTestRecord');
		$handler = $meta->getTypeHandler('unknown');
		$this->assertNull($handler);
	}
	
	/**
	 * @covers Amiss\Active\Meta::getTypeHandler
	 */
	public function testGetTypeHandlerFromParent()
	{
		$meta = new Meta(__NAMESPACE__.'\MetaTestRecord', new Meta(__NAMESPACE__.'\MetaTestParentRecord'));
		$handler = $meta->getTypeHandler('bar');
		$this->assertInstanceOf(__NAMESPACE__.'\MetaTestTypeHandler', $handler);
		$this->assertEquals(1, $handler->id);
	}
	
	/**
	 * @covers Amiss\Active\Meta::getTypeHandler
	 */
	public function testGetTypeHandlerFromParentCaches()
	{
		$mockParent = $this->getMock(
			'Amiss\Active\Meta', 
			array('getTypeHandler'), 
			array(__NAMESPACE__.'\MetaTestParentRecord')
		);
		$meta = new Meta(__NAMESPACE__.'\MetaTestRecord', $mockParent);
		
		$mockParent->expects($this->once())->method('getTypeHandler')->will($this->returnValue(new MetaTestTypeHandler(1)));
		$handler = $meta->getTypeHandler('bar');
		$this->assertEquals(1, $handler->id);
		
		$mockParent->expects($this->never())->method('getTypeHandler');
		$handler = $meta->getTypeHandler('bar');
		$this->assertEquals(1, $handler->id);
	}
	
	/**
	 * @covers Amiss\Active\Meta::getTypeHandler
	 */
	public function testGetUnknownTypeHandlerFromParentCaches()
	{
		$mockParent = $this->getMock(
			'Amiss\Active\Meta', 
			array('getTypeHandler'), 
			array(__NAMESPACE__.'\MetaTestParentRecord')
		);
		$meta = new Meta(__NAMESPACE__.'\MetaTestRecord', $mockParent);
		
		$mockParent->expects($this->once())->method('getTypeHandler')->will($this->returnValue(null));
		$handler = $meta->getTypeHandler('bar');
		$this->assertFalse($handler);
		
		$mockParent->expects($this->never())->method('getTypeHandler');
		$handler = $meta->getTypeHandler('bar');
		$this->assertFalse($handler);
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
	public $id;
	
	public function __construct($id)
	{
		$this->id = $id;
	}
	
	function prepareValueForDb($value, $object, $fieldName)
	{
		return 'db';
	}
	
	function handleValueFromDb($value, $object, $fieldName)
	{
		return 'value';
	}
	
	function createColumnType($engine)
	{}
}

class MetaTestParentRecord extends \Amiss\Active\Record
{
	public static function getTypeHandlers()
	{
		return array(
			'bar'=>new MetaTestTypeHandler(1),
		);
	}
}

class MetaTestRecord extends MetaTestParentRecord
{
	public static function getTypeHandlers()
	{
		return array(
			'foo'=>new MetaTestTypeHandler(2),
		);
	}
}
