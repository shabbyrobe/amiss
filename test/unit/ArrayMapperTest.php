<?php
namespace Amiss\Test\Unit;

use Amiss\Mapper\Arrays;

/**
 * @group unit
 */
class ArrayMapperTest extends \CustomTestCase
{
    /**
     * @covers Amiss\Mapper\Arrays::createMeta
     * @expectedException InvalidArgumentException
     */
    public function testCreateMetaWithUnknown()
    {
        $mapper = new Arrays(array());
        $this->callProtected($mapper, 'createMeta', 'awekawer');
    }

    /**
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
     * @covers Amiss\Mapper\Arrays::createMeta
     */
    public function testInherit()
    {
        $name = 'c'.md5(uniqid('', true));
        $name2 = $name.'2';
        $this->createClass($name, 'class '.$name.'{} class '.$name2.' extends '.$name.'{}');
        $mappings = array(
            $name=>array(),
            $name2=>array('inherit'=>true),
        );
        
        $mapper = new Arrays($mappings);
        $meta = $mapper->getMeta($name2);
        
        $parent = $this->getProtected($meta, 'parent');
        $this->assertEquals($name, $parent->class);
    }

    /**
     * @covers Amiss\Mapper\Arrays::createMeta
     */
    public function testNoInheritByDefault()
    {
        $name = 'c'.md5(uniqid('', true));
        $name2 = $name.'2';
        $this->createClass($name, 'class '.$name.'{} class '.$name2.' extends '.$name.'{}');
        $mappings = array(
            $name=>array(),
            $name2=>array(),
        );
        
        $mapper = new Arrays($mappings);
        $meta = $mapper->getMeta($name2);
        
        $parent = $this->getProtected($meta, 'parent');
        $this->assertNull($parent);
    }

    /**
     * @covers Amiss\Mapper\Arrays::__construct
     */
    public function testConstruct()
    {
        $mappings = array('a');
        $mapper = new Arrays($mappings);
        $this->assertEquals($mappings, $mapper->arrayMap);
    }
    
    /**
     * @covers Amiss\Mapper\Arrays::createMeta
     */
    public function testArrayFieldStrings()
    {
        $mappings = array(
            'foo'=>array('fields'=>array('a'=>true, 'b'=>true, 'c'=>true)),
        );
        $mapper = new Arrays($mappings);
        $meta = $mapper->getMeta('foo');
        
        $expected = array(
            'a'=>array('id'=>'a', 'name'=>'a', 'type'=>null),
            'b'=>array('id'=>'b', 'name'=>'b', 'type'=>null),
            'c'=>array('id'=>'c', 'name'=>'c', 'type'=>null),
        );
        $this->assertEquals($expected, $meta->getFields());
    }

    /**
     * @covers Amiss\Mapper\Arrays::createMeta
     */
    public function testArrayPrimaryDoesNotInferField()
    {
        $mappings = array(
            'foo'=>array('primary'=>'id'),
        );
        $mapper = new Arrays($mappings);
        $meta = $mapper->getMeta('foo');
        
        $this->assertEquals([], $meta->getFields());
    }

    /**
     * @covers Amiss\Mapper\Arrays::createMeta
     */
    public function testArrayPrimaryExplicitField()
    {
        $mappings = array(
            'foo'=>array('primary'=>'id', 'fields'=>array('id'=>array('type'=>array('id'=>'foobar')))),
        );
        $mapper = new Arrays($mappings);
        $meta = $mapper->getMeta('foo');
        
        $expected = array(
            'id'=>array('id'=>'id', 'name'=>'id', 'type'=>['id'=>'foobar']),
        );
        $this->assertEquals($expected, $meta->getFields());
    }
    
    /**
     * Tests issue #7
     * @covers Amiss\Mapper\Arrays::createMeta
     */
    public function testArrayPrimaryExplicitFieldTypeWithFieldName()
    {
        $mappings = array(
            'foo'=>array(
                'primary'=>'id', 
                'fields'=>array(
                    'id'=>array('name'=>'pants', 'type'=>array('id'=>'foobar'))
                )
            ),
        );
        $mapper = new Arrays($mappings);
        $mapper->defaultPrimaryType = 'flobadoo';
        $meta = $mapper->getMeta('foo');
        
        $expected = array(
            'id'=>array('id'=>'id', 'name'=>'pants', 'type'=>array('id'=>'foobar')),
        );
        
        $this->assertEquals($expected, $meta->getFields());
    }

    /**
     * @covers Amiss\Mapper\Arrays::createMeta
     */
    public function testArrayPrimaryUnnamedFieldTranslation()
    {
        $mappings = array(
            'foo'=>array(
                'primary'=>'fooBarBaz',
                'fields'=>array(
                    'fooBarBaz'=>[],
                    'bazQuxDing'=>[],
                ),
            ),
        );

        $mapper = new Arrays($mappings);
        $mapper->unnamedPropertyTranslator = new \Amiss\Name\CamelToUnderscore();
        $meta = $mapper->getMeta('foo');
        
        $fields = $meta->getFields();
        $this->assertEquals('foo_bar_baz',  $fields['fooBarBaz']['name']);
        $this->assertEquals('baz_qux_ding', $fields['bazQuxDing']['name']);
    }
}
