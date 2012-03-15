<?php

namespace Amiss\Test\Acceptance;

use Amiss\Demo;

class MapperTest extends \CustomTestCase
{
	/**
	 * @group unit
	 * @group mapper
	 * @covers Amiss\Mapper::resolveObjectName
	 */
	public function testResolveObjectNameWithNonNamespacedName()
	{
		$mapper = $this->getMockBuilder('Amiss\Mapper')->getMockForAbstractClass();
		$mapper->objectNamespace = 'abcd';
		$found = $mapper->resolveObjectName('foobar');
		$this->assertEquals('abcd\foobar', $found);
	}

	/**
	 * @group unit
	 * @group mapper
	 * @covers Amiss\Mapper::resolveObjectName
	 */
	public function testResolveObjectNameWithNamespacedName()
	{
		$mapper = $this->getMockBuilder('Amiss\Mapper')->getMockForAbstractClass();
		$mapper->objectNamespace = 'abcd';
		$found = $mapper->resolveObjectName('efgh\foobar');
		$this->assertEquals('efgh\foobar', $found);
	}

	/**
	 * @group unit
	 * @group mapper
	 * @covers Amiss\Mapper::resolveObjectName
	 */
	public function testResolveObjectNameWithoutNamespaceWhenNoNamespaceSet()
	{
		$mapper = $this->getMockBuilder('Amiss\Mapper')->getMockForAbstractClass();
		$mapper->objectNamespace = null;
		$found = $mapper->resolveObjectName('foobar');
		$this->assertEquals('foobar', $found);
	}
	
	/**
	 * @group unit
	 * @group mapper
	 * @covers Amiss\Mapper::getProperty
	 */
	public function testGetProperty()
	{
		$mapper = $this->getMockBuilder('Amiss\Mapper')->getMockForAbstractClass();
		
		$meta = new \Amiss\Meta('stdClass', 'stdClass', array());
		$object = (object)array('a'=>'foo');
		$value = $mapper->getProperty($meta, $object, 'a');
		$this->assertEquals('foo', $value);
	}
	
	/**
	 * @group unit
	 * @group mapper
	 * @covers Amiss\Mapper::getProperty
	 */
	public function testGetPropertyFromGetter()
	{
		$mapper = $this->getMockBuilder('Amiss\Mapper')->getMockForAbstractClass();
		
		$name = __FUNCTION__;
		eval("
			namespace ".__NAMESPACE__."; 
			class $name { 
				private \$_a = 'foo'; 
				public function getA() { return \$this->_a; }
			}
		");
		
		$name = __NAMESPACE__.'\\'.$name;
		$meta = new \Amiss\Meta('stdClass', 'stdClass', array('fields'=>array('a'=>array('getter'=>'getA'))));
		$object = new $name;
		
		$value = $mapper->getProperty($meta, $object, 'a');
		$this->assertEquals('foo', $value);
	}
	
	/**
	 * @group unit
	 * @group mapper
	 * @covers Amiss\Mapper::setProperty
	 */
	public function testSetProperty()
	{
		$mapper = $this->getMockBuilder('Amiss\Mapper')->getMockForAbstractClass();
		
		$meta = new \Amiss\Meta('stdClass', 'stdClass', array());
		$object = (object)array('a'=>null);
		$mapper->setProperty($meta, $object, 'a', 'foo');
		$this->assertEquals('foo', $object->a);
	}
	
	/**
	 * @group unit
	 * @group mapper
	 * @covers Amiss\Mapper::setProperty
	 */
	public function testSetPropertyFromGetter()
	{
		$mapper = $this->getMockBuilder('Amiss\Mapper')->getMockForAbstractClass();
		
		$name = __FUNCTION__;
		eval("
			namespace ".__NAMESPACE__."; 
			class $name { 
				public \$_a; 
				public function setA(\$value) { \$this->_a = \$value; }
			}
		");
		
		$name = __NAMESPACE__.'\\'.$name;
		$meta = new \Amiss\Meta('stdClass', 'stdClass', array('fields'=>array('a'=>array('setter'=>'setA'))));
		$object = new $name;
		
		$mapper->setProperty($meta, $object, 'a', 'foo');
		$this->assertEquals('foo', $object->_a);
	}
}
