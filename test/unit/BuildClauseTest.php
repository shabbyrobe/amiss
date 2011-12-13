<?php

namespace Amiss\Test\Unit;

use Amiss\Criteria;

class BuildClauseTest extends \CustomTestCase
{
	/**
	 * @covers Amiss\Criteria\Query::buildClause
	 */
	public function testInClause()
	{
		$criteria = new Criteria\Query;
		$criteria->params = array(':foo'=>array(1, 2, 3));
		$criteria->where = 'bar IN(:foo)';
		
		list ($where, $params) = $criteria->buildClause();
		$this->assertEquals('bar IN(:foo_0,:foo_1,:foo_2)', $where);
		$this->assertEquals(array(':foo_0'=>1, ':foo_1'=>2, ':foo_2'=>3), $params);
	}
	
	/**
	 * @covers Amiss\Criteria\Query::buildClause
	 * @dataProvider dataForInClauseReplacementTolerance
	 */
	public function testInClauseReplacementTolerance($clause)
	{
		$criteria = new Criteria\Query;
		$criteria->params = array(':foo'=>array(1, 2, 3));
		$criteria->where = $clause;
		
		list ($where, $params) = $criteria->buildClause();
		$this->assertRegexp('@bar\s+IN\(:foo_0,:foo_1,:foo_2\)@', $where);
		$this->assertEquals(array(':foo_0'=>1, ':foo_1'=>2, ':foo_2'=>3), $params);
	}
	
	public function dataForInClauseReplacementTolerance()
	{
		return array(
			array("bar IN(:foo)"),
			array("bar in (:foo)"),
			array("bar\nin\n(:foo)"),
		);
	}
	
	/**
	 * @covers Amiss\Criteria\Query::buildClause
	 */
	public function testMultipleInClause()
	{
		$criteria = new Criteria\Query;
		$criteria->params = array(
			':foo'=>array(1, 2),
			':baz'=>array(4, 5),
		);
		$criteria->where = 'bar IN(:foo) AND qux IN(:baz)';
		
		list ($where, $params) = $criteria->buildClause();
		$this->assertEquals('bar IN(:foo_0,:foo_1) AND qux IN(:baz_0,:baz_1)', $where);
		$this->assertEquals(array(':foo_0'=>1, ':foo_1'=>2, ':baz_0'=>4, ':baz_1'=>5), $params);
	}
	
	/**
	 * @covers Amiss\Criteria\Query::buildClause
	 * @dataProvider dataForInClauseDoesNotRuinString
	 */
	public function testInClauseDoesNotRuinString($where, $result)
	{
		$criteria = new Criteria\Query;
		$criteria->params = array(
			':foo'=>array(1, 2),
			':bar'=>array(3, 4),
		);
		$criteria->where = $where;
		
		list ($where, $params) = $criteria->buildClause();
		$this->assertEquals($result, $where);
		$this->assertEquals(array(':foo_0'=>1, ':foo_1'=>2, ':bar_0'=>3, ':bar_1'=>4), $params);
	}
	
	public function dataForInClauseDoesNotRuinString()
	{
		return array(
			array('foo IN (:foo) AND bar="hey :bar"',      'foo IN(:foo_0,:foo_1) AND bar="hey :bar"'),
			array('foo IN (:foo) AND bar="hey IN(:bar)"',  'foo IN(:foo_0,:foo_1) AND bar="hey IN(:bar)"'),
		);
	}
	
	/**
	 * @covers Amiss\Criteria\Query::buildClause
	 */
	public function testBuildClauseWithoutParameterColons()
	{
		$criteria = new Criteria\Query;
		$criteria->params = array('foo'=>1, 'baz'=>2);
		$criteria->where = 'bar=:foo AND qux=:baz';
		
		list ($where, $params) = $criteria->buildClause();
		$this->assertEquals(array(':foo'=>1, ':baz'=>2), $params);
		$this->assertEquals('bar=:foo AND qux=:baz', $where);
	}
	
	/**
	 * @covers Amiss\Criteria\Query::buildClause
	 */
	public function testShorthandWhere()
	{
		$criteria = new Criteria\Query;
		$criteria->where = array('bar'=>'yep', 'qux'=>'sub');
		
		list ($where, $params) = $criteria->buildClause();
		$this->assertEquals(array(':bar'=>'yep', ':qux'=>'sub'), $params);
		$this->assertEquals('`bar`=:bar AND `qux`=:qux', $where);
	}
}
