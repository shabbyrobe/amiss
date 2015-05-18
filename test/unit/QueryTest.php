<?php
namespace Amiss\Test\Unit;

use Amiss\Sql\Query;

/**
 * @group unit
 */
class QueryTest extends \CustomTestCase
{
    /**
     * @covers Amiss\Sql\Query\Criteria::buildClause
     */
    public function testInClauseStraight()
    {
        $criteria = new Query\Criteria;
        $criteria->params = array(':foo'=>array(1, 2, 3));
        $criteria->where = 'bar IN(:foo)';
        
        list ($where, $params) = $criteria->buildClause(null);
        $this->assertEquals('bar IN(:zp_0,:zp_1,:zp_2)', $where);
        $this->assertEquals(array(':zp_0'=>1, ':zp_1'=>2, ':zp_2'=>3), $params);
    }

    /**
     * @covers Amiss\Sql\Query\Criteria::buildClause
     */
    public function testInClauseWithFieldMapping()
    {
        $criteria = new Query\Criteria;
        $meta = new \Amiss\Meta('stdClass', array(
            'table'=>'std_class',
            'fields'=>array(
                'foo'=>array('name'=>'foo_field'),
                'bar'=>array('name'=>'bar_field'),
            ),
        ));
        $criteria->params = array(':foo'=>array(1, 2, 3));
        $criteria->where = '{bar} IN(:foo)';
        
        list ($where, $params) = $criteria->buildClause($meta);
        $this->assertEquals('`bar_field` IN(:zp_0,:zp_1,:zp_2)', $where);
        $this->assertEquals(array(':zp_0'=>1, ':zp_1'=>2, ':zp_2'=>3), $params);
    }
    
    /**
     * @covers Amiss\Sql\Query\Criteria::buildClause
     * @dataProvider dataForInClauseReplacementTolerance
     */
    public function testInClauseReplacementTolerance($clause)
    {
        $criteria = new Query\Criteria;
        $criteria->params = array(':foo'=>array(1, 2, 3));
        $criteria->where = $clause;
        
        list ($where, $params) = $criteria->buildClause(null);
        $this->assertRegexp('@bar\s+IN\(:zp_0,:zp_1,:zp_2\)@', $where);
        $this->assertEquals(array(':zp_0'=>1, ':zp_1'=>2, ':zp_2'=>3), $params);
    }
    
    public function dataForInClauseReplacementTolerance()
    {
        return array(
            array("bar IN(:foo)"),
            array("bar in (:foo)"),
            array("bar in ( :foo )"),
            array("bar in ( \n :foo \n )"),
            array("bar\nin\n(:foo)"),
        );
    }
    
    /**
     * @covers Amiss\Sql\Query\Criteria::buildClause
     */
    public function testMultipleInClause()
    {
        $criteria = new Query\Criteria;
        $criteria->params = array(
            ':foo'=>array(1, 2),
            ':baz'=>array(4, 5),
        );
        $criteria->where = 'bar IN(:foo) AND qux IN(:baz)';
        
        list ($where, $params) = $criteria->buildClause(null);
        $this->assertEquals('bar IN(:zp_0,:zp_1) AND qux IN(:zp_2,:zp_3)', $where);
        $this->assertEquals(array(':zp_0'=>1, ':zp_1'=>2, ':zp_2'=>4, ':zp_3'=>5), $params);
    }

    /**
     * @covers Amiss\Sql\Query\Criteria::buildClause
     */
    public function testMultipleFieldSameIn()
    {
        $criteria = new Query\Criteria;
        $criteria->params = array(
            ':foo'=>array(1, 2),
        );
        $criteria->where = 'bar IN(:foo) AND qux IN(:foo)';
        
        list ($where, $params) = $criteria->buildClause(null);
        $this->assertEquals('bar IN(:zp_0,:zp_1) AND qux IN(:zp_0,:zp_1)', $where);
        $this->assertEquals(array(':zp_0'=>1, ':zp_1'=>2), $params);
    }

    /**
     * @covers Amiss\Sql\Query\Criteria::buildClause
     */
    public function testBuildClauseWithoutParameterColons()
    {
        $criteria = new Query\Criteria;
        $criteria->params = array('foo'=>1, 'baz'=>2);
        $criteria->where = 'bar=:foo AND qux=:baz';
        
        list ($where, $params) = $criteria->buildClause(null);
        $this->assertEquals(array(':foo'=>1, ':baz'=>2), $params);
        $this->assertEquals('bar=:foo AND qux=:baz', $where);
    }
    
    /**
     * @covers Amiss\Sql\Query\Criteria::buildClause
     */
    public function testShorthandWhere()
    {
        $criteria = new Query\Criteria;
        $criteria->where = array('bar'=>'yep', 'qux'=>'sub');
        
        list ($where, $params) = $criteria->buildClause(null);
        $this->assertEquals(array(':zp_0'=>'yep', ':zp_1'=>'sub'), $params);
        $this->assertEquals('`bar`=:zp_0 AND `qux`=:zp_1', $where);
    }

    /**
     * @covers Amiss\Sql\Query\Criteria::buildClause
     * @dataProvider dataForBuildClauseFieldSubstitutionWithFromRawSql
     */
    public function testBuildClauseFieldSubstitutionWithFromRawSql($query, $expected)
    { 
        $criteria = new Query\Criteria;
        $meta = new \Amiss\Meta('stdClass', array(
            'table'=>'std_class',
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
     * @covers Amiss\Sql\Query\Criteria::buildClause
     * @dataProvider dataForBuildClauseFromArrayWithFieldSubstitution
     */
    public function testBuildClauseFieldSubstitutionWithArray($query, $expected)
    {
        $criteria = new Query\Criteria;
        $meta = new \Amiss\Meta('stdClass', array(
            'table'=>'std_class',
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
            array(array('foo'=>'foo', 'bar'=>'bar'), '`foo_field`=:zp_0 AND `bar_field`=:zp_1'),
            
            // with one explicit column and one property
            array(array('foo_fieldy'=>'foo', 'bar'=>'bar'), '`foo_fieldy`=:zp_0 AND `bar_field`=:zp_1'),
        );
    }
    
    /**
     * @covers Amiss\Sql\Query\Criteria::paramsAreNamed
     * @dataProvider dataForParamsAreNamed
     */
    public function testParamsAreNamed($name, $areNamed, $params)
    {
        $criteria = new Query\Criteria;
        $criteria->params = $params;
        $this->assertEquals($areNamed, $criteria->paramsAreNamed(), $name.' failed');
    }
    
    public function dataForParamsAreNamed()
    {
        return array(
            array('non-named', false, array('a', 'b', 'c')),
            array('some named', true, array('a', 'q'=>'b', 'c')),
            array('all named', true, array('a'=>'a', 'q'=>'b', 'c'=>'d')),
            array('messy named', true, array('0'=>'a', null=>'b', 1=>'d')),
            array('messy mixed', true, array('0'=>'a', null=>'b', '1'=>'d')),
        );
    }
}
