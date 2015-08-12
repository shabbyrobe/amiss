<?php
namespace Amiss\Test\Unit;

use Amiss\Mapper\Arrays;
use Amiss\Test\Helper\ClassBuilder;

/**
 * @group unit
 */
class ArrayMapperTest extends \Amiss\Test\Helper\TestCase
{
    public $fieldDefaults = ['type'=>['id'=>null], 'required'=>false];

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
     * @covers Amiss\Mapper\Arrays::__construct
     */
    public function testConstruct()
    {
        $mappings = array('a');
        $mapper = new Arrays($mappings);
        $this->assertEquals($mappings, $mapper->mappings);
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
        
        $expected = [
            'a' => ['id'=>'a', 'name'=>'a'] + $this->fieldDefaults,
            'b' => ['id'=>'b', 'name'=>'b'] + $this->fieldDefaults,
            'c' => ['id'=>'c', 'name'=>'c'] + $this->fieldDefaults,
        ];
        $this->assertEquals($expected, $meta->fields);
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
        
        $this->assertEquals([], $meta->fields);
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
        
        $expected = [
            'id'=>['id'=>'id', 'name'=>'id', 'type'=>['id'=>'foobar'], 'required'=>false],
        ];
        $this->assertEquals($expected, $meta->fields);
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
        
        $expected = [
            'id'=>['id'=>'id', 'name'=>'pants', 'type'=>['id'=>'foobar'], 'required'=>false],
        ];
        
        $this->assertEquals($expected, $meta->fields);
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
        
        $fields = $meta->fields;
        $this->assertEquals('foo_bar_baz',  $fields['fooBarBaz']['name']);
        $this->assertEquals('baz_qux_ding', $fields['bazQuxDing']['name']);
    }
}
