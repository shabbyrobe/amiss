<?php

namespace Amiss\Test\Unit;

use Amiss\Manager;

class ManagerCreateSelectCriteriaTest extends \CustomTestCase
{	
	public function setUp()
	{
		$this->manager = new Manager(
			array('dsn'=>'sqlite::memory:'),
			new \Amiss\Mapper\Note
		);
	}
	
	/**
	 * @covers Amiss\Manager::createSelectCriteria
	 * @covers Amiss\Manager::populateQueryCriteria
	 */
	public function testHandlePositionalShorthandUnrolled()
	{
		$args = array('pants=? AND foo=?', 'pants', 'foo');
		$criteria = $this->callProtected($this->manager, 'createSelectCriteria', $args);
		
		$this->assertEquals(array('pants', 'foo'), $criteria->params);
		$this->assertEquals('pants=? AND foo=?', $criteria->where);
	}
	
	/**
	 * @covers Amiss\Manager::createSelectCriteria
	 * @covers Amiss\Manager::populateQueryCriteria
	 */
	public function testHandlePositionalShorthandRolled()
	{
		$args = array('pants=? AND foo=?', array('pants', 'foo'));
		$criteria = $this->callProtected($this->manager, 'createSelectCriteria', $args);
		
		$this->assertEquals(array('pants', 'foo'), $criteria->params);
		$this->assertEquals('pants=? AND foo=?', $criteria->where);
	}
	
	/**
	 * @covers Amiss\Manager::createSelectCriteria
	 * @covers Amiss\Manager::populateQueryCriteria
	 */
	public function testHandlePositionalLongform()
	{
		$args = array(array('where'=>'pants=? AND foo=?', 'params'=>array('pants', 'foo')));
		$criteria = $this->callProtected($this->manager, 'createSelectCriteria', $args);
		
		$this->assertEquals(array('pants', 'foo'), $criteria->params);
		$this->assertEquals('pants=? AND foo=?', $criteria->where);
	}
	
	/**
	 * @covers Amiss\Manager::createSelectCriteria
	 * @covers Amiss\Manager::populateQueryCriteria
	 */
	public function testHandleNamedShorthand()
	{
		$args = array('pants=:pants AND foo=:foo', array(':pants'=>'pants', ':foo'=>'foo'));
		$criteria = $this->callProtected($this->manager, 'createSelectCriteria', $args);
		
		$this->assertEquals(array(':pants'=>'pants', ':foo'=>'foo'), $criteria->params);
		$this->assertEquals('pants=:pants AND foo=:foo', $criteria->where);
	}
	
	/**
	 * @covers Amiss\Manager::createSelectCriteria
	 * @covers Amiss\Manager::populateQueryCriteria
	 */
	public function testHandleNamedLongform()
	{
		$args = array(array('where'=>'pants=:pants AND foo=:foo', 'params'=>array(':pants'=>'pants', ':foo'=>'foo')));
		$criteria = $this->callProtected($this->manager, 'createSelectCriteria', $args);
		
		$this->assertEquals(array(':pants'=>'pants', ':foo'=>'foo'), $criteria->params);
		$this->assertEquals('pants=:pants AND foo=:foo', $criteria->where);
	}
}
