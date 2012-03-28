<?php

namespace Amiss\Test\Unit;

use Amiss\Criteria;

class BuildClauseTest extends \CustomTestCase
{
	/**
	 * @group unit
	 * @covers Amiss\Criteria\Query::buildClause
	 */
	public function testInClauseStraight()
	{
		$criteria = new Criteria\Query;
		$criteria->params = array(':foo'=>array(1, 2, 3));
		$criteria->where = 'bar IN(:foo)';
		
		list ($where, $params) = $criteria->buildClause(null);
		$this->assertEquals('bar IN(:foo_0,:foo_1,:foo_2)', $where);
		$this->assertEquals(array(':foo_0'=>1, ':foo_1'=>2, ':foo_2'=>3), $params);
	}

	/**
	 * @group unit
	 * @covers Amiss\Criteria\Query::buildClause
	 */
	public function testInClauseWithFieldMapping()
	{
		$criteria = new Criteria\Query;
		$meta = new \Amiss\Meta('stdClass', 'std_class', array(
			'fields'=>array(
				'foo'=>array('name'=>'foo_field'),
				'bar'=>array('name'=>'bar_field'),
			),
		));
		$criteria->params = array(':foo'=>array(1, 2, 3));
		$criteria->where = '{bar} IN(:foo)';
		
		list ($where, $params) = $criteria->buildClause($meta);
		$this->assertEquals('`bar_field` IN(:foo_0,:foo_1,:foo_2)', $where);
		$this->assertEquals(array(':foo_0'=>1, ':foo_1'=>2, ':foo_2'=>3), $params);
	}
	
	/**
	 * @group unit
	 * @covers Amiss\Criteria\Query::buildClause
	 * @dataProvider dataForInClauseReplacementTolerance
	 */
	public function testInClauseReplacementTolerance($clause)
	{
		$criteria = new Criteria\Query;
		$criteria->params = array(':foo'=>array(1, 2, 3));
		$criteria->where = $clause;
		
		list ($where, $params) = $criteria->buildClause(null);
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
	 * @group unit
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
		
		list ($where, $params) = $criteria->buildClause(null);
		$this->assertEquals('bar IN(:foo_0,:foo_1) AND qux IN(:baz_0,:baz_1)', $where);
		$this->assertEquals(array(':foo_0'=>1, ':foo_1'=>2, ':baz_0'=>4, ':baz_1'=>5), $params);
	}
	
	/**
	 * @group unit
	 * @group faulty
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
		
		list ($where, $params) = $criteria->buildClause(null);
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
	 * @group unit
	 * @covers Amiss\Criteria\Query::buildClause
	 */
	public function testBuildClauseWithoutParameterColons()
	{
		$criteria = new Criteria\Query;
		$criteria->params = array('foo'=>1, 'baz'=>2);
		$criteria->where = 'bar=:foo AND qux=:baz';
		
		list ($where, $params) = $criteria->buildClause(null);
		$this->assertEquals(array(':foo'=>1, ':baz'=>2), $params);
		$this->assertEquals('bar=:foo AND qux=:baz', $where);
	}
	
	/**
	 * @group unit
	 * @covers Amiss\Criteria\Query::buildClause
	 */
	public function testShorthandWhere()
	{
		$criteria = new Criteria\Query;
		$criteria->where = array('bar'=>'yep', 'qux'=>'sub');
		
		list ($where, $params) = $criteria->buildClause(null);
		$this->assertEquals(array(':bar'=>'yep', ':qux'=>'sub'), $params);
		$this->assertEquals('`bar`=:bar AND `qux`=:qux', $where);
	}

	/**
	 * @group unit
	 * @covers Amiss\Criteria\Query::buildClause
	 * @dataProvider dataForBuildClauseFieldSubstitutionWithFromRawSql
	 */
	public function testBuildClauseFieldSubstitutionWithFromRawSql($query, $expected)
	{ 
		$criteria = new Criteria\Query;
		$meta = new \Amiss\Meta('stdClass', 'std_class', array(
			'fields'=>array(
				'foo'=>array('name'=>'foo_field'),
				'bar'=>array('name'=>'bar_field'),
			),
		));
		$criteria->where = $query;
		list ($where, $params) = $criteria->buildClause($meta);
		$this->assertEquals($expected, $where);
	}
	
	public function dataForBuildClauseFieldSubstitutionWithFromRawSql()
	{
		return array(
			// with two properties
			array("{foo}=:foo AND {bar}=:bar", '`foo_field`=:foo AND `bar_field`=:bar'),
			
			// with one explicit column and one property
			array("blibbidy=:foo AND {bar}=:bar", 'blibbidy=:foo AND `bar_field`=:bar'),			
		);
	}

	/**
	 * @group unit
	 * @covers Amiss\Criteria\Query::buildClause
	 * @dataProvider dataForBuildClauseFromArrayWithFieldSubstitution
	 */
	public function testBuildClauseFieldSubstitutionWithArray($query, $expected)
	{
		$criteria = new Criteria\Query;
		$meta = new \Amiss\Meta('stdClass', 'std_class', array(
			'fields'=>array(
				'foo'=>array('name'=>'foo_field'),
				'bar'=>array('name'=>'bar_field'),
			),
		));
		$criteria->where = $query;
		list ($where, $params) = $criteria->buildClause($meta);
		$this->assertEquals($expected, $where);
	}
	
	public function dataForBuildClauseFromArrayWithFieldSubstitution()
	{
		return array(
			// with two properties
			array(array('foo'=>'foo', 'bar'=>'bar'), '`foo_field`=:foo_field AND `bar_field`=:bar_field'),
			
			// with one explicit column and one property
			array(array('foo_fieldy'=>'foo', 'bar'=>'bar'), '`foo_fieldy`=:foo_fieldy AND `bar_field`=:bar_field'),
		);
	}
}
