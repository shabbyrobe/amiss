<?php

namespace Amiss\Test\Unit;

use Amiss\Manager;

class ManagerCreateObjectTest extends \CustomTestCase
{
	public function setUp()
	{
		$this->manager = new Manager(array());
		$this->manager->objectNamespace = 'Amiss\Test\Unit';
	}
	
	/**
	 * @covers Amiss\Manager::fetchObject
	 */
	public function testDefaultFetchObject()
	{
		$stmt = new MockStatement();
		$stmt->fetchReturn = array(
			'foo'=>'bar',
			'baz'=>'qux',
		);
		
		$object = $this->manager->fetchObject($stmt, 'TestCreateObject');
		
		$this->assertTrue($object instanceof TestCreateObject);
		$this->assertEquals('bar', $object->foo);
		$this->assertEquals('qux', $object->baz);
	}
	
	/**
	 * @covers Amiss\Manager::fetchObject
	 */
	public function testCustomFetchObject()
	{
		$stmt = new MockStatement();
		$stmt->fetchReturn = array(
			'foo'=>'bar',
			'baz'=>'qux',
		);
		
		$object = $this->manager->fetchObject($stmt, 'TestCustomCreateObject');
		
		$this->assertTrue($object instanceof TestCustomCreateObject);
		$this->assertEquals('1bar1', $object->foo);
		$this->assertEquals('1qux1', $object->baz);
	}
}

class TestCustomCreateObject implements \Amiss\RowBuilder
{
	public $foo;
	public $baz;
	
	public function buildObject(array $row)
	{
		$this->foo = '1'.$row['foo'].'1';
		$this->baz = '1'.$row['baz'].'1';
	}
}

class TestCreateObject
{
	public $foo;
	public $baz;
}

class MockStatement
{
	public $fetchReturn;
	
	public function fetch()
	{
		return $this->fetchReturn;
	}
}
