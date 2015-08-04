<?php
namespace Amiss\Test\Acceptance;

/**
 * @group unit
 */
class MetaTest extends \Amiss\Test\Helper\TestCase
{
    public $fieldDefaults = ['type'=>['id'=>null], 'required'=>false];

    /**
     * @covers Amiss\Meta::__construct
     */
    public function testCreateMeta()
    {
        $parent = new \Amiss\Meta('a', array('table'=>'a'));
        $info = array(
            'table'=>'std_class',
            'primary'=>'pri',
            'fields'=>array('f'=>array()),
            'relations'=>array('r'=>array()),
            'defaultFieldType'=>'def',
        );
        $meta = new \Amiss\Meta('stdClass', $info, $parent);
        
        $this->assertEquals('stdClass',   $meta->class);
        $this->assertEquals('std_class',  $meta->table);
        $this->assertEquals(array('pri'), $meta->primary);
        
        $this->assertEquals(['f'=>['id'=>'f', 'name'=>'f'] + $this->fieldDefaults], $this->getProtected($meta, 'fields'));
        $this->assertEquals(['r'=>['id'=>'r', 'mode'=>'default']], $this->getProtected($meta, 'relations'));
        $this->assertEquals(['id'=>'def'],  $this->getProtected($meta, 'defaultFieldType'));
    }

    /**
     * @covers Amiss\Meta::setFields
     */
    public function testPrimaryDefinedInInfoAndFieldsFails()
    {
        $this->setExpectedException(
            \Amiss\Exception::class, 
            "Primary can not be defined at class level and field level simultaneously in class 'stdClass'"
        );
        $meta = new \Amiss\Meta('stdClass', array(
            'table'=>'std_class',
            'primary'=>'a',
            'fields'=>[
                'a'=>['primary'=>true],
            ],
        ));
    }

    /**
     * @covers Amiss\Meta::__construct
     * @covers Amiss\Meta::getIndexValue
     */
    public function testGetIndexValueString()
    {
        $meta = new \Amiss\Meta('stdClass', array(
            'table'=>'std_class',
            'primary'=>'a',
            'fields'=>[
                'a'=>true, 'b'=>true,
            ],
        ));
        
        $obj = (object)array('a'=>1, 'b'=>2);
        $this->assertEquals(array('a'=>1), $meta->getIndexValue($obj));
    }

    /**
     * @covers Amiss\Meta::__construct
     * @covers Amiss\Meta::getIndexValue
     */
    public function testGetIndexValueSingleCol()
    {
        $meta = new \Amiss\Meta('stdClass', array(
            'table'=>'std_class',
            'primary'=>array('a'),
            'fields'=>[
                'a'=>true, 'b'=>true,
            ],
        ));
        
        $obj = (object)array('a'=>1, 'b'=>2);
        $this->assertEquals(array('a'=>1), $meta->getIndexValue($obj));
    }

    /**
     * @covers Amiss\Meta::__construct
     * @covers Amiss\Meta::getIndexValue
     */
    public function testGetIndexValueMultiCol()
    {
        $meta = new \Amiss\Meta('stdClass', array(
            'table'=>'std_class',
            'primary'=>array('a', 'b'),
            'fields'=>[
                'a'=>true, 'b'=>true,
            ],
        ));
        
        $obj = (object)array('a'=>1, 'b'=>2);
        $this->assertEquals(array('a'=>1, 'b'=>2), $meta->getIndexValue($obj));
    }

    /**
     * @covers Amiss\Meta::__construct
     * @covers Amiss\Meta::getIndexValue
     */
    public function testGetIndexValueMultiReturnsNullWhenNoValues()
    {
        $meta = new \Amiss\Meta('stdClass', array(
            'table'=>'std_class',
            'primary'=>array('a', 'b'),
            'fields'=>[
                'a'=>true, 'b'=>true, 'c'=>true,
            ],
        ));
        
        $obj = (object)array('a'=>null, 'b'=>null, 'c'=>3);
        $this->assertEquals(null, $meta->getIndexValue($obj));
    }

    /**
     * @covers Amiss\Meta::getIndexValue
     */
    public function testGetIndexValueMultiWhenOneValueIsNull()
    {
        $meta = new \Amiss\Meta('stdClass', array(
            'table'=>'std_class',
            'primary'=>array('a', 'b'),
            'fields'=>[
                'a'=>true, 'b'=>true, 'c'=>true,
            ],
        ));
        
        $obj = (object)array('a'=>null, 'b'=>2, 'c'=>3);
        $this->assertEquals(array('a'=>null, 'b'=>2), $meta->getIndexValue($obj));
    }

    /**
     * @covers Amiss\Meta::getValue
     */
    public function testGetPropertyValue()
    {
        $meta = new \Amiss\Meta('stdClass', array(
            'table'=>'std_class',
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
        $meta = new \Amiss\Meta('stdClass', array(
            'table'=>'std_class',
            'fields'=>array(
                'a'=>array(),
            ),
        ));
        
        $obj = (object)array('a'=>'foo');
        
        $this->setExpectedException(\InvalidArgumentException::class, "Unknown property 'b' on stdClass");
        $result = $meta->getValue($obj, 'b');
    }
    
    /**
     * @covers Amiss\Meta::getValue
     */
    public function testGetGetterValue()
    {
        $meta = new \Amiss\Meta('stdClass', array(
            'table'=>'std_class',
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
        $meta = new \Amiss\Meta('stdClass', array(
            'table'=>'std_class',
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
        $meta = new \Amiss\Meta('stdClass', array(
            'table'=>'std_class',
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
        $meta = new \Amiss\Meta('stdClass', array(
            'table'=>'std_class',
            'fields'=>array(
                'a'=>array(),
            ),
        ));
        
        $object = (object) array('a'=>null);
        $this->setExpectedException(\InvalidArgumentException::class, "Unknown property 'doesntExist' on stdClass");
        $meta->setValue($object, 'doesntExist', 'foo');
    }

    /**
     * @covers Amiss\Meta::setValue
     */
    public function testSetValueWithSetter()
    {
        $meta = new \Amiss\Meta('stdClass', array(
            'table'=>'std_class',
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
        $meta = new \Amiss\Meta('stdClass', array(
            'table'=>'std_class',
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

    /**
     * @covers Amiss\Meta::__sleep
     */
    public function testSleep()
    {
        $m = new \Amiss\Meta('stdClass', []);
        $props = $m->__sleep();
        $rc = new \ReflectionClass($m);
        foreach ($rc->getProperties() as $p) {
            $this->assertContains($p->name, $props, "You forgot to add '{$p->name}' to Amiss\Meta's __sleep()");
        }
    }
}
