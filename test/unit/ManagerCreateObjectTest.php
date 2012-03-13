<?php

namespace Amiss\Test\Unit;

use Amiss\Manager;

class ManagerCreateObjectTest extends \CustomTestCase
{
	public function setUp()
	{
		$this->manager = new Manager(array());
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
