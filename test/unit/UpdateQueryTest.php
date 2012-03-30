<?php

namespace Amiss\Test\Unit;

use Amiss\Criteria\Update;

class UpdateQueryTest extends \CustomTestCase
{
	/**
	 * @group unit
	 * @covers Amiss\Criteria\Update::buildSet
	 */
	public function testBuildNamedSetWithoutMeta()
	{
		$uq = $this->getMock('Amiss\Criteria\Update', array('paramsAreNamed'));
		$uq->expects($this->any())->method('paramsAreNamed')->will($this->returnValue(true));
		
		$uq->set = array('foo_foo'=>'bar', 'baz_baz'=>'qux');
		
		list ($clause, $params) = $uq->buildSet(null);
		$this->assertEquals('`foo_foo`=:set_foo_foo, `baz_baz`=:set_baz_baz', $clause);
		$this->assertEquals(array(':set_foo_foo'=>'bar', ':set_baz_baz'=>'qux'), $params);
	}

	/**
	 * @group unit
	 * @covers Amiss\Criteria\Update::buildSet
	 */
	public function testBuildArraySetWithSomeManualClauses()
	{
		$uq = $this->getMock('Amiss\Criteria\Update', array('paramsAreNamed'));
		$uq->expects($this->any())->method('paramsAreNamed')->will($this->returnValue(true));
		
		$uq->set = array('foo_foo'=>'bar', 'baz_baz'=>'qux', 'dingdong=dangdung+1');
		
		list ($clause, $params) = $uq->buildSet(null);
		$this->assertEquals('`foo_foo`=:set_foo_foo, `baz_baz`=:set_baz_baz, dingdong=dangdung+1', $clause);
		$this->assertEquals(array(':set_foo_foo'=>'bar', ':set_baz_baz'=>'qux'), $params);
	}
	
	/**
	 * @group unit
	 * @covers Amiss\Criteria\Update::buildSet
	 */
	public function testBuildPositionalSetWithoutMeta()
	{
		$uq = $this->getMock('Amiss\Criteria\Update', array('paramsAreNamed'));
		$uq->expects($this->any())->method('paramsAreNamed')->will($this->returnValue(false));
		
		$uq->set = array('foo_foo'=>'bar', 'baz_baz'=>'qux');
		
		list ($clause, $params) = $uq->buildSet(null);
		$this->assertEquals('`foo_foo`=?, `baz_baz`=?', $clause);
		$this->assertEquals(array('bar', 'qux'), $params);
	}

	/**
	 * @group unit
	 * @covers Amiss\Criteria\Update::buildSet
	 */
	public function testBuildNamedSetWithMeta()
	{
		$uq = $this->getMock('Amiss\Criteria\Update', array('paramsAreNamed'));
		$uq->expects($this->any())->method('paramsAreNamed')->will($this->returnValue(true));
		
		$uq->set = array('fooFoo'=>'baz', 'barBar'=>'qux');
		
		$meta = $this->createGenericMeta();
		list ($clause, $params) = $uq->buildSet($meta);
		$this->assertEquals('`foo_field`=:set_fooFoo, `bar_field`=:set_barBar', $clause);
		$this->assertEquals(array(':set_fooFoo'=>'baz', ':set_barBar'=>'qux'), $params);
	}
	
	/**
	 * @group unit
	 * @covers Amiss\Criteria\Update::buildSet
	 */
	public function testBuildPositionalSetWithMeta()
	{
		$uq = $this->getMock('Amiss\Criteria\Update', array('paramsAreNamed'));
		$uq->expects($this->any())->method('paramsAreNamed')->will($this->returnValue(false));
		
		$uq->set = array('fooFoo'=>'baz', 'barBar'=>'qux');
		
		$meta = $this->createGenericMeta();
		list ($clause, $params) = $uq->buildSet($meta);
		$this->assertEquals('`foo_field`=?, `bar_field`=?', $clause);
		$this->assertEquals(array('baz', 'qux'), $params);
	}
	
	protected function createGenericMeta()
	{
		return new \Amiss\Meta('stdClass', 'std_class', array(
			'fields'=>array(
				'fooFoo'=>array('name'=>'foo_field'),
				'barBar'=>array('name'=>'bar_field'),
			),
		));
	}
}
