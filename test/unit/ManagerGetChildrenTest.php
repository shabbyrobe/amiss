<?php
namespace Amiss\Test\Unit;

use Amiss\Sql\Manager;

class ManagerGetChildrenTest extends \CustomTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->manager = new Manager(array(), new \Amiss\Mapper\Note);
    }
    
    /**
     * @group unit
     * @group manager
     * 
     * @covers Amiss\Sql\Manager::getChildren
     */
    public function testGetFirstLevelScalarChildrenWithStringPath()
    {
        $objects = array(
            (object)array('foo'=>(object)array('bar'=>'baz')),
            (object)array('foo'=>(object)array('bar'=>'qux')),
        );
        $children = $this->manager->getChildren($objects, 'foo');
        $this->assertEquals(array($objects[0]->foo, $objects[1]->foo), $children);
    }
    
    /**
     * @group unit
     * @group manager
     * 
     * @covers Amiss\Sql\Manager::getChildren
     */
    public function testGetFirstLevelScalarChildrenWithArrayPath()
    {
        $objects = array(
            (object)array('foo'=>(object)array('bar'=>'baz')),
            (object)array('foo'=>(object)array('bar'=>'qux')),
        );
        $children = $this->manager->getChildren($objects, array('foo'));
        $this->assertEquals(array($objects[0]->foo, $objects[1]->foo), $children);
    }
    
    /**
     * @group unit
     * @group manager
     * 
     * @covers Amiss\Sql\Manager::getChildren
     */
    public function testGetSecondLevelScalarChildrenWithStringPath()
    {
        $objects = array(
            (object)array('foo'=>(object)array('bar'=>(object)array('baz'=>'qux'))),
            (object)array('foo'=>(object)array('bar'=>(object)array('baz'=>'doink'))),
        );
        $children = $this->manager->getChildren($objects, 'foo/bar');
        $this->assertEquals(array($objects[0]->foo->bar, $objects[1]->foo->bar), $children);
    }
    
    /**
     * @group unit
     * @group manager
     * 
     * @covers Amiss\Sql\Manager::getChildren
     */
    public function testGetSecondLevelScalarChildrenWithArrayPath()
    {
        $objects = array(
            (object)array('foo'=>(object)array('bar'=>(object)array('baz'=>'qux'))),
            (object)array('foo'=>(object)array('bar'=>(object)array('baz'=>'doink'))),
        );
        $children = $this->manager->getChildren($objects, array('foo', 'bar'));
        $this->assertEquals(array($objects[0]->foo->bar, $objects[1]->foo->bar), $children);
    }
    
    /**
     * @group unit
     * @group manager
     * 
     * @covers Amiss\Sql\Manager::getChildren
     */
    public function testGetFirstLevelArrayChildren()
    {
        $objects = array(
            (object)array('foo'=>array((object)array('bar'=>'baz'),   (object)array('bar'=>'qux'))),
            (object)array('foo'=>array((object)array('bar'=>'doink'), (object)array('bar'=>'boing'))),
        );
        $children = $this->manager->getChildren($objects, 'foo');
        $this->assertEquals(array($objects[0]->foo[0], $objects[0]->foo[1], $objects[1]->foo[0], $objects[1]->foo[1]), $children);
    }
    
    /**
     * @group unit
     * @group manager
     * 
     * @covers Amiss\Sql\Manager::getChildren
     */
    public function testGetMultiLevelArrayChildren()
    {
        $result = array(
            new TestObject(array('baz'=>'qux')),
            new TestObject(array('baz'=>'doink')),
            new TestObject(array('baz'=>'boing')),
            new TestObject(array('baz'=>'ting')),
            new TestObject(array('baz'=>'dong')),
            new TestObject(array('baz'=>'bang')),
            new TestObject(array('baz'=>'clang')),
            new TestObject(array('baz'=>'blam')),
        );
        
        $objects = array(
            new TestObject(array('foo'=>array(
                new TestObject(array('bar'=>array($result[0], $result[1]))),
                new TestObject(array('bar'=>array($result[2], $result[3]))),
            ))),
            new TestObject(array('foo'=>array(
                new TestObject(array('bar'=>array($result[4], $result[5]))),
                new TestObject(array('bar'=>array($result[6], $result[7]))),
            ))),
        );
        
        $children = $this->manager->getChildren($objects, 'foo/bar');
        $this->assertEquals($result, $children);
    }
}

class TestObject
{
    public function __construct($properties=array())
    {
        foreach ($properties as $k=>$v) $this->$k = $v;
    }
}
