<?php
namespace Amiss\Test\Unit;

use Amiss\Test\Factory;
use Amiss\Test\Helper\ClassBuilder;

/**
 * @group unit
 */
class FilterGetChildrenTest extends \Amiss\Test\Helper\TestCase
{
    /**
     * @group manager
     * 
     * @covers Amiss\Filter::getChildren
     */
    public function testGetFirstLevelScalarChildrenWithStringPath()
    {
        $objects = [
            (object) ['foo'=>(object) ['bar'=>'baz']],
            (object) ['foo'=>(object) ['bar'=>'qux']],
        ];
        $filter = new \Amiss\Filter(new \Amiss\Mapper\Note);
        $children = $filter->getChildren($objects, 'foo');
        $this->assertEquals([$objects[0]->foo, $objects[1]->foo], $children);
    }

    /**
     * @group manager
     * 
     * @covers Amiss\Filter::getChildren
     */
    public function testGetFirstLevelScalarChildrenWithArrayPath()
    {
        $objects = array(
            (object) ['foo'=>(object) ['bar'=>'baz']],
            (object) ['foo'=>(object) ['bar'=>'qux']],
        );
        $filter = new \Amiss\Filter(new \Amiss\Mapper\Note);
        $children = $filter->getChildren($objects, ['foo']);
        $this->assertEquals([$objects[0]->foo, $objects[1]->foo], $children);
    }
    
    /**
     * @group manager
     * 
     * @covers Amiss\Filter::getChildren
     */
    public function testGetSecondLevelScalarChildrenWithStringPath()
    {
        $objects = [
            (object) ['foo'=>(object) ['bar'=>(object) ['baz'=>'qux']]],
            (object) ['foo'=>(object) ['bar'=>(object) ['baz'=>'doink']]],
        ];
        $filter = new \Amiss\Filter(new \Amiss\Mapper\Note);
        $children = $filter->getChildren($objects, 'foo/bar');
        $this->assertEquals([$objects[0]->foo->bar, $objects[1]->foo->bar], $children);
    }
    
    /**
     * @group manager
     * 
     * @covers Amiss\Filter::getChildren
     */
    public function testGetSecondLevelScalarChildrenWithArrayPath()
    {
        $objects = [
            (object) ['foo'=>(object) ['bar'=>(object) ['baz'=>'qux']]],
            (object) ['foo'=>(object) ['bar'=>(object) ['baz'=>'doink']]],
        ];
        $filter = new \Amiss\Filter(new \Amiss\Mapper\Note);
        $children = $filter->getChildren($objects, ['foo', 'bar']);
        $this->assertEquals([$objects[0]->foo->bar, $objects[1]->foo->bar], $children);
    }
    
    /**
     * @group manager
     * 
     * @covers Amiss\Filter::getChildren
     */
    public function testGetFirstLevelArrayChildren()
    {
        $objects = [
            (object) ['foo'=>[(object) ['bar'=>'baz'],   (object) ['bar'=>'qux']]],
            (object) ['foo'=>[(object) ['bar'=>'doink'], (object) ['bar'=>'boing']]],
        ];
        $filter = new \Amiss\Filter(new \Amiss\Mapper\Note);
        $children = $filter->getChildren($objects, 'foo');
        $this->assertEquals([$objects[0]->foo[0], $objects[0]->foo[1], $objects[1]->foo[0], $objects[1]->foo[1]], $children);
    }
    
    /**
     * @group manager
     * 
     * @covers Amiss\Filter::getChildren
     */
    public function testGetMultiLevelArrayChildren()
    {
        $result = [
            new TestObject(['baz'=>'qux']),
            new TestObject(['baz'=>'doink']),
            new TestObject(['baz'=>'boing']),
            new TestObject(['baz'=>'ting']),
            new TestObject(['baz'=>'dong']),
            new TestObject(['baz'=>'bang']),
            new TestObject(['baz'=>'clang']),
            new TestObject(['baz'=>'blam']),
        ];
        
        $objects = [
            new TestObject(['foo'=>[
                new TestObject(['bar'=>[$result[0], $result[1]]]),
                new TestObject(['bar'=>[$result[2], $result[3]]]),
            ]]),
            new TestObject(['foo'=>[
                new TestObject(['bar'=>[$result[4], $result[5]]]),
                new TestObject(['bar'=>[$result[6], $result[7]]]),
            ]]),
        ];
        
        $filter = new \Amiss\Filter(new \Amiss\Mapper\Note);
        $children = $filter->getChildren($objects, 'foo/bar');
        $this->assertEquals($result, $children);
    }
}

class TestObject
{
    public function __construct($properties=[])
    {
        foreach ($properties as $k=>$v) $this->$k = $v;
    }
}
