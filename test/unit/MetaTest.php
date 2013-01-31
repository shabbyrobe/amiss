<?php
namespace Amiss\Test\Acceptance;

class MetaTest extends \CustomTestCase
{
    /**
     * @covers Amiss\Meta::__construct
     * @group unit
     */
    public function testCreateMeta()
    {
        $parent = new \Amiss\Meta('a', 'a', array());
        $info = array(
            'primary'=>'pri',
            'fields'=>array('f'=>array()),
            'relations'=>array('r'=>array()),
            'defaultFieldType'=>'def',
        );
        $meta = new \Amiss\Meta('stdClass', 'std_class', $info, $parent);
        
        $this->assertEquals('stdClass', $meta->class);
        $this->assertEquals('std_class', $meta->table);
        $this->assertEquals(array('pri'), $meta->primary);
        $this->assertEquals(array('f'=>array()), $this->getProtected($meta, 'fields'));
        $this->assertEquals(array('r'=>array()), $this->getProtected($meta, 'relations'));
        $this->assertEquals('def', $this->getProtected($meta, 'defaultFieldType'));
    }
    
    /**
     * @covers Amiss\Meta::getFields
     * @group unit
     */
    public function testGetFieldInheritance()
    {
        $grandparent = new \Amiss\Meta('a', 'a', array(
            'fields'=>array(
                'field1'=>array(),
                'field2'=>array(),
            ),
        )); 
        $parent = new \Amiss\Meta('b', 'b', array(
            'fields'=>array(
                'field3'=>array(),
                'field4'=>array(1),
            ),
        ), $grandparent);
        $child = new \Amiss\Meta('c', 'c', array(
            'fields'=>array(
                'field4'=>array(2),
                'field5'=>array(),
            ),
        ), $parent);
        
        $expected = array(
            'field1'=>array(),
            'field2'=>array(),
            'field3'=>array(),
            'field4'=>array(2),
            'field5'=>array(),
        );
        $this->assertEquals($expected, $child->getFields());
    }
    
    /**
     * @group unit
     * @covers Amiss\Meta::getPrimaryValue
     */
    public function testGetPrimaryValueSingleCol()
    {
        $meta = new \Amiss\Meta('stdClass', 'std_class', array(
            'primary'=>array('a'),
        ));
        
        $obj = (object)array('a'=>1, 'b'=>2);
        $this->assertEquals(array('a'=>1), $meta->getPrimaryValue($obj));
    }

    /**
     * @group unit
     * @covers Amiss\Meta::getPrimaryValue
     */
    public function testGetPrimaryValueMultiCol()
    {
        $meta = new \Amiss\Meta('stdClass', 'std_class', array(
            'primary'=>array('a', 'b'),
        ));
        
        $obj = (object)array('a'=>1, 'b'=>2);
        $this->assertEquals(array('a'=>1, 'b'=>2), $meta->getPrimaryValue($obj));
    }

    /**
     * @group unit
     * @covers Amiss\Meta::getPrimaryValue
     */
    public function testGetPrimaryValueMultiReturnsNullWhenNoValues()
    {
        $meta = new \Amiss\Meta('stdClass', 'std_class', array(
            'primary'=>array('a', 'b'),
        ));
        
        $obj = (object)array('a'=>null, 'b'=>null, 'c'=>3);
        $this->assertEquals(null, $meta->getPrimaryValue($obj));
    }

    /**
     * @group unit
     * @covers Amiss\Meta::getPrimaryValue
     */
    public function testGetPrimaryValueMultiWhenOneValueIsNull()
    {
        $meta = new \Amiss\Meta('stdClass', 'std_class', array(
            'primary'=>array('a', 'b'),
        ));
        
        $obj = (object)array('a'=>null, 'b'=>2, 'c'=>3);
        $this->assertEquals(array('a'=>null, 'b'=>2), $meta->getPrimaryValue($obj));
    }
}
