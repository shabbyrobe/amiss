<?php

namespace Amiss\Test\Unit;

use Amiss\Criteria\Update;

class UpdateBuildSetTest extends \CustomTestCase
{
	/**
	 * @group unit
	 */
	public function testBuildNamedSet()
	{
		$uq = $this->getMock('Amiss\Criteria\Update', array('paramsAreNamed'));
		$uq->expects($this->any())->method('paramsAreNamed')->will($this->returnValue(true));
		
		$uq->set = array('foo'=>'bar', 'baz'=>'qux');
		
		list ($clause, $params) = $uq->buildSet();
		$this->assertEquals('`foo`=:set_foo, `baz`=:set_baz', $clause);
		$this->assertEquals(array(':set_foo'=>'bar', ':set_baz'=>'qux'), $params);
	}
	
	/**
	 * @group unit
	 */
	public function testBuildPositionalSet()
	{
		$uq = $this->getMock('Amiss\Criteria\Update', array('paramsAreNamed'));
		$uq->expects($this->any())->method('paramsAreNamed')->will($this->returnValue(false));
		
		$uq->set = array('foo'=>'bar', 'baz'=>'qux');
		
		list ($clause, $params) = $uq->buildSet();
		$this->assertEquals('`foo`=?, `baz`=?', $clause);
		$this->assertEquals(array('bar', 'qux'), $params);
	}
}
