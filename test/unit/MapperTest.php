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
		$mapper = $this->getMockBuilder('Amiss\Mapper\Base')->getMockForAbstractClass();
		$mapper->objectNamespace = 'abcd';
		$found = $this->callProtected($mapper, 'resolveObjectName', 'foobar');
		$this->assertEquals('abcd\foobar', $found);
	}

	/**
	 * @group unit
	 * @group mapper
	 * @covers Amiss\Mapper::resolveObjectName
	 */
	public function testResolveObjectNameWithNamespacedName()
	{
		$mapper = $this->getMockBuilder('Amiss\Mapper\Base')->getMockForAbstractClass();
		$mapper->objectNamespace = 'abcd';
		$found = $this->callProtected($mapper, 'resolveObjectName', 'efgh\foobar');
		$this->assertEquals('efgh\foobar', $found);
	}

	/**
	 * @group unit
	 * @group mapper
	 * @covers Amiss\Mapper::resolveObjectName
	 */
	public function testResolveObjectNameWithoutNamespaceWhenNoNamespaceSet()
	{
		$mapper = $this->getMockBuilder('Amiss\Mapper\Base')->getMockForAbstractClass();
		$mapper->objectNamespace = null;
		$found = $this->callProtected($mapper, 'resolveObjectName', 'foobar');
		$this->assertEquals('foobar', $found);
	}
}
