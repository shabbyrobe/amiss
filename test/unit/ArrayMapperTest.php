<?php
namespace Amiss\Test\Unit;

use Amiss\Mapper\Arrays;

class ArrayMapperTest extends \CustomTestCase
{
    public function setUp()
    {
    }
    
    /**
     * @group unit
     * @covers Amiss\Mapper\Arrays::createMeta
     * @expectedException InvalidArgumentException
     */
    public function testCreateMetaWithUnknown()
    {
        $mapper = new Arrays(array());
        $this->callProtected($mapper, 'createMeta', 'awekawer');
    }

    /**
     * @group unit
     * @covers Amiss\Mapper\Arrays::createMeta
     */
    public function testArrayWithoutTableUsesDefault()
    {
        $mappings = array(
            'foo'=>array(
                'fields'=>array(),
            ),
        );
        $mapper = $this->getMockBuilder('Amiss\Mapper\Arrays')
            ->setMethods(array('getDefaultTable'))
            ->setConstructorArgs(array($mappings))
            ->getMock()
        ;
        $mapper->expects($this->once())->method('getDefaultTable')->will($this->returnValue('abc'));
        $meta = $mapper->getMeta('foo');
        $this->assertEquals('abc', $meta->table);
    }
    
    /**
     * @group unit
     * @covers Amiss\Mapper\Arrays::createMeta
     */
    public function testInheritTrue()
    {
        $name = 'c'.md5(uniqid('', true));
        $name2 = $name.'2';
        eval('class '.$name.'{} class '.$name2.' extends '.$name.'{}');
        $mappings = array(
            $name=>array(),
            $name2=>array(),
        );
        
        $mapper = new Arrays($mappings);
        $mapper->inherit = true;
        $meta = $mapper->getMeta($name2);
        
        $parent = $this->getProtected($meta, 'parent');
        $this->assertEquals($name, $parent->class);
    }
    
    /**
     * @group unit
     * @covers Amiss\Mapper\Arrays::createMeta
     */
    public function testInheritFalse()
    {
        $name = 'c'.md5(uniqid('', true));
        $name2 = $name.'2';
        eval('class '.$name.'{} class '.$name2.' extends '.$name.'{}');
        $mappings = array(
            $name=>array(),
            $name2=>array(),
        );
        
        $mapper = new Arrays($mappings);
        $mapper->inherit = false;
        $meta = $mapper->getMeta($name2);
        
        $parent = $this->getProtected($meta, 'parent');
        $this->assertEquals(null, $parent);
    }
    
    /**
     * @group unit
     * @covers Amiss\Mapper\Arrays::__construct
     */
    public function testConstruct()
    {
        $mappings = array('a');
        $mapper = new Arrays($mappings);
        $this->assertEquals($mappings, $mapper->arrayMap);
    }
    
    /**
     * @group unit
     * @covers Amiss\Mapper\Arrays::createMeta
     */
    public function testArrayFieldStrings()
    {
        $mappings = array(
            'foo'=>array('fields'=>array('a', 'b', 'c')),
        );
        $mapper = new Arrays($mappings);
        $meta = $mapper->getMeta('foo');
        
        $expected = array(
            'a'=>array('name'=>'a', 'type'=>null),
            'b'=>array('name'=>'b', 'type'=>null),
            'c'=>array('name'=>'c', 'type'=>null),
        );
        $this->assertEquals($expected, $meta->getFields());
    }

    /**
     * @group unit
     * @covers Amiss\Mapper\Arrays::createMeta
     */
    public function testArrayPrimaryInfersField()
    {
        $mappings = array(
            'foo'=>array('primary'=>'id'),
        );
        $mapper = new Arrays($mappings);
        $meta = $mapper->getMeta('foo');
        
        $expected = array(
            'id'=>array('name'=>'id', 'type'=>'autoinc'),
        );
        $this->assertEquals($expected, $meta->getFields());
    }

    /**
     * @group unit
     * @covers Amiss\Mapper\Arrays::createMeta
     */
    public function testArrayPrimaryInferredFieldDefaultType()
    {
        $mappings = array(
            'foo'=>array('primary'=>'id'),
        );
        $mapper = new Arrays($mappings);
        $mapper->defaultPrimaryType = 'flobadoo';
        $meta = $mapper->getMeta('foo');
        
        $expected = array(
            'id'=>array('name'=>'id', 'type'=>'flobadoo'),
        );
        $this->assertEquals($expected, $meta->getFields());
    }
    
    /**
     * @group unit
     * @covers Amiss\Mapper\Arrays::createMeta
     */
    public function testArrayDeclaredPrimaryFieldWithoutTypeAssumesDefaultPrimaryType()
    {
        $mappings = array(
            'foo'=>array('primary'=>'id', 'fields'=>array('id')),
        );
        $mapper = new Arrays($mappings);
        $mapper->defaultPrimaryType = 'flobadoo';
        $meta = $mapper->getMeta('foo');
        
        $expected = array(
            'id'=>array('name'=>'id', 'type'=>'flobadoo'),
        );
        $this->assertEquals($expected, $meta->getFields());
    }
    
    /**
     * @group unit
     * @covers Amiss\Mapper\Arrays::createMeta
     */
    public function testArrayPrimaryExplicitFieldType()
    {
        $mappings = array(
            'foo'=>array('primary'=>'id', 'fields'=>array('id'=>array('type'=>'foobar'))),
        );
        $mapper = new Arrays($mappings);
        $mapper->defaultPrimaryType = 'flobadoo';
        $meta = $mapper->getMeta('foo');
        
        $expected = array(
            'id'=>array('name'=>'id', 'type'=>'foobar'),
        );
        $this->assertEquals($expected, $meta->getFields());
    }
    
    /**
     * Tests issue #7
     * @group unit
     * @covers Amiss\Mapper\Arrays::createMeta
     */
    public function testArrayPrimaryExplicitFieldTypeWithFieldName()
    {
        $mappings = array(
            'foo'=>array(
                'primary'=>'id', 
                'fields'=>array(
                    'id'=>array('name'=>'pants', 'type'=>'foobar')
                )
            ),
        );
        $mapper = new Arrays($mappings);
        $mapper->defaultPrimaryType = 'flobadoo';
        $meta = $mapper->getMeta('foo');
        
        $expected = array(
            'id'=>array('name'=>'pants', 'type'=>'foobar'),
        );
        
        $this->assertEquals($expected, $meta->getFields());
    }
    
    /**
     * Tests issue #7
     * @group unit
     * @covers Amiss\Mapper\Arrays::createMeta
     */
    public function testArrayPrimaryImplicitFieldTypeWithFieldName()
    {
        $mappings = array(
            'foo'=>array(
                'primary'=>'id', 
                'fields'=>array(
                    'id'=>array('name'=>'pants')
                )
            ),
        );
        $mapper = new Arrays($mappings);
        $mapper->defaultPrimaryType = 'flobadoo';
        $meta = $mapper->getMeta('foo');
        
        $expected = array(
            'id'=>array('name'=>'pants', 'type'=>'flobadoo'),
        );
        
        $this->assertEquals($expected, $meta->getFields());
    }
    
    /**
     * @group unit
     * @covers Amiss\Mapper\Arrays::createMeta
     */
    public function test()
    {
        
    }
}
