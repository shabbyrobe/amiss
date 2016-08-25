<?php
namespace Amiss\Test\Unit;

use Amiss\Meta;

use Amiss\Sql\Query\Update;

/**
 * @group unit
 */
class UpdateQueryTest extends \Amiss\Test\Helper\TestCase
{
    /**
     * @covers Amiss\Sql\Query\Update::buildQuery
     */
    public function testBuildQueryWithArrayWhere()
    {
        $uq = new Update;
        $uq->where = array('a'=>'b');
        $uq->set = array('c'=>'d');
        
        $meta = new Meta('Foo', array('table'=>'foo'));
        list($sql, $params) = $uq->buildQuery($meta);
        $this->assertEquals('UPDATE `foo` SET `c`=:zs_0 WHERE `a`=:zp_1', $sql);
        $this->assertEquals(array(':zs_0'=>'d', ':zp_1'=>'b'), $params);
    }

    /**
     * @covers Amiss\Sql\Query\Update::buildQuery
     */
    public function testBuildQueryWithStringWhereContainingNamedParams()
    {
        $uq = new Update;
        $uq->where = 'foo=:bar';
        $uq->params = array('bar'=>'ding');
        $uq->set = array('c'=>'d');
        
        $meta = new Meta('Foo', array('table'=>'foo'));
        list($sql, $params) = $uq->buildQuery($meta);
        $this->assertEquals('UPDATE `foo` SET `c`=:zs_0 WHERE foo=:bar', $sql);
        $this->assertEquals(array(':zs_0'=>'d', ':bar'=>'ding'), $params);
    }

    /**
     * @covers Amiss\Sql\Query\Update::buildQuery
     */
    public function testBuildQueryWithStringWhereContainingPositionalParams()
    {
        $uq = new Update;
        $uq->where = 'foo=?';
        $uq->params = array('ding');
        $uq->set = array('c'=>'d');
        
        $meta = new Meta('Foo', array('table'=>'foo'));
        list($sql, $params) = $uq->buildQuery($meta);
        $this->assertEquals('UPDATE `foo` SET `c`=? WHERE foo=?', $sql);
        $this->assertEquals(array('d', 'ding'), $params);
    }
    
    /**
     * @covers Amiss\Sql\Query\Update::buildSet
     */
    public function testBuildNamedSetWithoutMeta()
    {
        $uq = $this->getMock('Amiss\Sql\Query\Update', array('paramsAreNamed'));
        $uq->expects($this->any())->method('paramsAreNamed')->will($this->returnValue(true));
        
        $uq->set = array('foo_foo'=>'bar', 'baz_baz'=>'qux');
        
        list ($clause, $params) = $uq->buildSet(null);
        $this->assertEquals('`foo_foo`=:zs_0, `baz_baz`=:zs_1', $clause);
        $this->assertEquals(array(':zs_0'=>'bar', ':zs_1'=>'qux'), $params);
    }

    /**
     * @covers Amiss\Sql\Query\Update::buildSet
     */
    public function testBuildArraySetWithSomeManualClauses()
    {
        $uq = $this->getMock('Amiss\Sql\Query\Update', array('paramsAreNamed'));
        $uq->expects($this->any())->method('paramsAreNamed')->will($this->returnValue(true));
        
        $uq->set = array('foo_foo'=>'bar', 'baz_baz'=>'qux', 'dingdong=dangdung+1');
        
        list ($clause, $params) = $uq->buildSet(null);
        $this->assertEquals('`foo_foo`=:zs_0, `baz_baz`=:zs_1, dingdong=dangdung+1', $clause);
        $this->assertEquals(array(':zs_0'=>'bar', ':zs_1'=>'qux'), $params);
    }
    
    /**
     * @covers Amiss\Sql\Query\Update::buildSet
     */
    public function testBuildPositionalSetWithoutMeta()
    {
        $uq = $this->getMock('Amiss\Sql\Query\Update', array('paramsAreNamed'));
        $uq->expects($this->any())->method('paramsAreNamed')->will($this->returnValue(false));
        
        $uq->set = array('foo_foo'=>'bar', 'baz_baz'=>'qux');
        
        list ($clause, $params) = $uq->buildSet(null);
        $this->assertEquals('`foo_foo`=?, `baz_baz`=?', $clause);
        $this->assertEquals(array('bar', 'qux'), $params);
    }

    /**
     * @covers Amiss\Sql\Query\Update::buildSet
     */
    public function testBuildNamedSetWithMeta()
    {
        $uq = $this->getMock('Amiss\Sql\Query\Update', array('paramsAreNamed'));
        $uq->expects($this->any())->method('paramsAreNamed')->will($this->returnValue(true));
        
        $uq->set = array('fooFoo'=>'baz', 'barBar'=>'qux');
        
        $meta = $this->createGenericMeta();
        list ($clause, $params) = $uq->buildSet($meta);
        $this->assertEquals('`foo_field`=:zs_0, `bar_field`=:zs_1', $clause);
        $this->assertEquals(array(':zs_0'=>'baz', ':zs_1'=>'qux'), $params);
    }
    
    /**
     * @covers Amiss\Sql\Query\Update::buildSet
     */
    public function testBuildPositionalSetWithMeta()
    {
        $uq = $this->getMock('Amiss\Sql\Query\Update', array('paramsAreNamed'));
        $uq->expects($this->any())->method('paramsAreNamed')->will($this->returnValue(false));
        
        $uq->set = array('fooFoo'=>'baz', 'barBar'=>'qux');
        
        $meta = $this->createGenericMeta();
        list ($clause, $params) = $uq->buildSet($meta);
        $this->assertEquals('`foo_field`=?, `bar_field`=?', $clause);
        $this->assertEquals(array('baz', 'qux'), $params);
    }
    
    protected function createGenericMeta()
    {
        return new \Amiss\Meta('stdClass', array(
            'table'=>'std_class',
            'fields'=>array(
                'fooFoo'=>array('name'=>'foo_field'),
                'barBar'=>array('name'=>'bar_field'),
            ),
        ));
    }
}
