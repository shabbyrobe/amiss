<?php
namespace Amiss\Test\Acceptance;

/**
 * @group unit
 */
class MetaTest extends \CustomTestCase
{
    /**
     * @covers Amiss\Meta::__construct
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
        
        $this->assertEquals('stdClass',   $meta->class);
        $this->assertEquals('std_class',  $meta->table);
        $this->assertEquals(array('pri'), $meta->primary);
        
        $this->assertEquals(array('f'=>array('name'=>'f')), $this->getProtected($meta, 'fields'));
        $this->assertEquals(array('r'=>array('name'=>'r')), $this->getProtected($meta, 'relations'));
        $this->assertEquals(array('id'=>'def'),  $this->getProtected($meta, 'defaultFieldType'));
    }
    
    /**
     * @covers Amiss\Meta::getDefaultFieldType
     */
    public function testGetDefaultFieldTypeInheritsFromDirectParent()
    {
        $parent = new \Amiss\Meta('parent', 'parent', array(
            'defaultFieldType'=>'def',
        ));
        $meta = new \Amiss\Meta('child', 'child', array(), $parent);
        $this->assertEquals(array('id'=>'def'), $meta->getDefaultFieldType());
    }
    
    /**
     * @covers Amiss\Meta::getDefaultFieldType
     */
    public function testGetDefaultFieldTypeInheritsFromGrandparent()
    {
        $grandParent = new \Amiss\Meta('grandparent', 'grandparent', array(
            'defaultFieldType'=>'def',
        ));
        $parent = new \Amiss\Meta('parent', 'parent', array(), $grandParent);
        $meta = new \Amiss\Meta('child', 'child', array(), $parent);
        $this->assertEquals(array('id'=>'def'), $meta->getDefaultFieldType());
    }
    
    /**
     * @covers Amiss\Meta::getDefaultFieldType
     * @dataProvider dataForGetDefaultFieldTypeFromParentOnlyCallsParentOnce
     */
    public function testGetDefaultFieldTypeFromParentOnlyCallsParentOnce($defaultType)
    {
        $parent = $this->getMockBuilder('Amiss\Meta')
            ->disableOriginalConstructor()
            ->setMethods(array('getDefaultFieldType'))
            ->getMock()
        ;
        $parent->expects($this->once())->method('getDefaultFieldType')->will($this->returnValue($defaultType));
        
        $meta = new \Amiss\Meta('child', 'child', array(), $parent);
        $meta->getDefaultFieldType();
        $meta->getDefaultFieldType();
    }
    
    public function dataForGetDefaultFieldTypeFromParentOnlyCallsParentOnce()
    {
        return array(
            array('yep'),
            array(array('id'=>'yep')),
            array(null),
            array(false),
        );
    }
    
    /**
     * @covers Amiss\Meta::getFields
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
            'field1'=>array('name'=>'field1'),
            'field2'=>array('name'=>'field2'),
            'field3'=>array('name'=>'field3'),
            'field4'=>array(2, 'name'=>'field4'),
            'field5'=>array('name'=>'field5'),
        );
        $this->assertEquals($expected, $child->getFields());
    }
    
    /**
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

    /**
     * @covers Amiss\Meta::getValue
     */
    public function testGetPropertyValue()
    {
        $meta = new \Amiss\Meta('stdClass', 'std_class', array(
            'fields'=>array(
                'a'=>array(),
            ),
        ));
        
        $obj = (object)array('a'=>'foo');
        $result = $meta->getValue($obj, 'a');
        $this->assertEquals('foo', $result);
    }
    
    /**
     * @covers Amiss\Meta::getValue
     */
    public function testGetUnknownPropertyValue()
    {
        $meta = new \Amiss\Meta('stdClass', 'std_class', array(
            'fields'=>array(
                'a'=>array(),
            ),
        ));
        
        $obj = (object)array('a'=>'foo');
        
        $this->setExpectedException('PHPUnit_Framework_Error_Notice');
        $result = $meta->getValue($obj, 'b');
    }
    
    /**
     * @covers Amiss\Meta::getValue
     */
    public function testGetGetterValue()
    {
        $meta = new \Amiss\Meta('stdClass', 'std_class', array(
            'fields'=>array(
                'a'=>array('getter'=>'getTest'),
            ),
        ));
        
        $mock = $this->getMockBuilder('stdClass')
            ->setMethods(array('getTest'))
            ->getMock()
        ;
        $mock->expects($this->once())->method('getTest');
        
        $result = $meta->getValue($mock, 'a');
    }
    
    /**
     * @covers Amiss\Meta::getValue
     */
    public function testGetUnknownGetterValue()
    {
        $meta = new \Amiss\Meta('stdClass', 'std_class', array(
            'fields'=>array(
                'a'=>array('getter'=>'getDoesntExist'),
            ),
        ));
        
        $mock = $this->getMockBuilder('stdClass')
            ->setMethods(array('getTest'))
            ->getMock()
        ;
        $mock->expects($this->never())->method('getTest');
        
        $this->setExpectedException('PHPUnit_Framework_Error_Warning');
        $result = $meta->getValue($mock, 'a');
    }
    
    /**
     * @covers Amiss\Meta::setValue
     */
    public function testSetValue()
    {
        $meta = new \Amiss\Meta('stdClass', 'std_class', array(
            'fields'=>array(
                'a'=>array(),
            ),
        ));
        
        $object = (object) array('a'=>null);
        $meta->setValue($object, 'a', 'foo');
        $this->assertEquals($object->a, 'foo');
    }
    
    /**
     * @covers Amiss\Meta::setValue
     */
    public function testSetUnknownValue()
    {
        $meta = new \Amiss\Meta('stdClass', 'std_class', array(
            'fields'=>array(
                'a'=>array(),
            ),
        ));
        
        $object = (object) array('a'=>null);
        $meta->setValue($object, 'doesntExist', 'foo');
        $this->assertEquals($object->doesntExist, 'foo');
    }

    /**
     * @covers Amiss\Meta::setValue
     */
    public function testSetValueWithSetter()
    {
        $meta = new \Amiss\Meta('stdClass', 'std_class', array(
            'fields'=>array(
                'a'=>array('setter'=>'setValue'),
            ),
        ));
        
        $object = $this->getMockBuilder('stdClass')
            ->setMethods(array('setValue'))
            ->getMock()
        ;
        $object->expects($this->once())->method('setValue');
        $meta->setValue($object, 'a', 'foo');
    }
    
    /**
     * @covers Amiss\Meta::setValue
     */
    public function testSetValueWithUnknownSetter()
    {
        $meta = new \Amiss\Meta('stdClass', 'std_class', array(
            'fields'=>array(
                'a'=>array('setter'=>'setDoesntExist'),
            ),
        ));
        
        $object = $this->getMockBuilder('stdClass')
            ->setMethods(array('setValue'))
            ->getMock()
        ;
        $object->expects($this->never())->method('setValue');
        
        $this->setExpectedException('PHPUnit_Framework_Error_Warning');
        $meta->setValue($object, 'a', 'foo');
    }
}
