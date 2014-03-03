<?php

namespace Amiss\Test\Unit;

use Amiss\Demo;

/**
 * @group unit
 * @group mapper
 */
class MapperTest extends \CustomTestCase
{
    /**
     * @covers Amiss\Mapper\Base::fromObjects
     */
    public function testFromObjects()
    {
        $mapper = $this->getMockBuilder('Amiss\Mapper\Base')
            ->setMethods(array('fromObject'))
            ->getMockForAbstractClass()
        ;
        $mapper->expects($this->exactly(2))->method('fromObject');
        $mapper->fromObjects('foo', array('a', 'b'), null);
    }
    
    /**
     * @covers Amiss\Mapper\Base::fromObjects
     */
    public function testFromObjectsWithNullInput()
    {
        $mapper = $this->getMockBuilder('Amiss\Mapper\Base')
            ->setMethods(array('fromObject'))
            ->getMockForAbstractClass()
        ;
        $mapper->expects($this->never())->method('fromObject');
        $mapper->fromObjects('foo', null, null);
    }

    /**
     * @covers Amiss\Mapper\Base::fromObject
     */
    public function testFromObjectWithSkipNulls()
    {
        $mapper = $this->getMockBuilder('Amiss\Mapper\Base')
            ->setMethods([])
            ->getMockForAbstractClass()
        ;
        $mapper->skipNulls = true;
        
        $meta = new \Amiss\Meta('stdClass', 'table', [
            'fields'=>[
                'a'=>['type'=>'text', 'name'=>'a'],
                'b'=>['type'=>'text', 'name'=>'b'],
                'c'=>['type'=>'text', 'name'=>'c'],
                'd'=>['type'=>'text', 'name'=>'d'],
                'e'=>['type'=>'text', 'name'=>'e'],
                'f'=>['type'=>'text', 'name'=>'f'],
            ],
        ]);
        $obj = (object)['a'=>'abcd', 'b'=>'efgh', 'c'=>false, 'd'=>0, 'e'=>null, 'f'=>null];
        $row = $mapper->fromObject($meta, $obj);
        $this->assertEquals(['a'=>'abcd', 'b'=>'efgh', 'c'=>false, 'd'=>0], $row);
    }
 
    /**
     * @covers Amiss\Mapper\Base::fromObject
     */
    public function testFromObjectWithoutSkipNulls()
    {
        $mapper = $this->getMockBuilder('Amiss\Mapper\Base')
            ->setMethods([])
            ->getMockForAbstractClass()
        ;
        $this->assertFalse($mapper->skipNulls);
        
        $meta = new \Amiss\Meta('stdClass', 'table', [
            'fields'=>[
                'a'=>['type'=>'text', 'name'=>'a'],
                'b'=>['type'=>'text', 'name'=>'b'],
                'c'=>['type'=>'text', 'name'=>'c'],
                'd'=>['type'=>'text', 'name'=>'d'],
                'e'=>['type'=>'text', 'name'=>'e'],
                'f'=>['type'=>'text', 'name'=>'f'],
            ],
        ]);
        $input = ['a'=>'abcd', 'b'=>'efgh', 'c'=>false, 'd'=>0, 'e'=>null, 'f'=>null];
        $obj = (object)$input;
        $row = $mapper->fromObject($meta, $obj);
        $this->assertEquals($input, $row);
    }

    /**
     * @covers Amiss\Mapper\Base::toObjects
     */
    public function testToObjects()
    {
        $mapper = $this->getMockBuilder('Amiss\Mapper\Base')
            ->setMethods(array('toObject'))
            ->getMockForAbstractClass()
        ;
        $mapper->expects($this->exactly(2))->method('toObject');
        $mapper->toObjects('foo', array('a', 'b'), null);
    }
    
    /**
     * @covers Amiss\Mapper\Base::resolveObjectName
     */
    public function testResolveObjectNameWithNonNamespacedName()
    {
        $mapper = $this->getMockBuilder('Amiss\Mapper\Base')->getMockForAbstractClass();
        $mapper->objectNamespace = 'abcd';
        $found = $this->callProtected($mapper, 'resolveObjectName', 'foobar');
        $this->assertEquals('abcd\foobar', $found);
    }
    
    /**
     * @group mapper
     * @covers Amiss\Mapper\Base::resolveObjectName
     */
    public function testResolveObjectNameWithNamespacedName()
    {
        $mapper = $this->getMockBuilder('Amiss\Mapper\Base')->getMockForAbstractClass();
        $mapper->objectNamespace = 'abcd';
        $found = $this->callProtected($mapper, 'resolveObjectName', 'efgh\foobar');
        $this->assertEquals('efgh\foobar', $found);
    }
    
    /**
     * @group mapper
     * @covers Amiss\Mapper\Base::resolveObjectName
     */
    public function testResolveObjectNameWithoutNamespaceWhenNoNamespaceSet()
    {
        $mapper = $this->getMockBuilder('Amiss\Mapper\Base')->getMockForAbstractClass();
        $mapper->objectNamespace = null;
        $found = $this->callProtected($mapper, 'resolveObjectName', 'foobar');
        $this->assertEquals('foobar', $found);
    }
    
    /**
     * @group mapper
     * @dataProvider dataForDefaultTableName
     * @covers Amiss\Mapper\Base::getDefaultTable
     */
    public function testDefaultTableNameWhenNoTranslatorSet($name, $result)
    {
        $mapper = $this->getMockBuilder('Amiss\Mapper\Base')->getMockForAbstractClass();
        $table = $this->callProtected($mapper, 'getDefaultTable', $name);
        $this->assertEquals($result, $table);
    }
    
    public function dataForDefaultTableName()
    {
        return array(
            array('Artist', '`artist`'),
            array('ArtistPants', '`artist_pants`'),
            array('ArtistPantsBurger', '`artist_pants_burger`'),
        );
    }
    
    /**
     * @group mapper
     * @dataProvider dataForDefaultTableNameWithTranslator
     * @covers Amiss\Mapper\Base::getDefaultTable
     */
    public function testDefaultTableNameWithTranslator($name, $result)
    {
        $mapper = $this->getMockBuilder('Amiss\Mapper\Base')->getMockForAbstractClass();
        $mapper->defaultTableNameTranslator = function ($class) {
            return 'woohoo';
        };
        $table = $this->callProtected($mapper, 'getDefaultTable', $name);
        $this->assertEquals($result, $table);
    }
    
    public function dataForDefaultTableNameWithTranslator()
    {
        return array(
            array('Artist', 'woohoo'),
            array('ArtistType', 'woohoo'),
            array('ArtistPantsBurger', 'woohoo'),
            array('', 'woohoo'),
        );
    }
    
    /**
     * @group mapper
     * @dataProvider dataForDefaultTableName
     * @covers Amiss\Mapper\Base::getDefaultTable
     */
    public function testDefaultTableNameFallbackWhenTranslatorReturnsNull($name, $result)
    {
        $mapper = $this->getMockBuilder('Amiss\Mapper\Base')->getMockForAbstractClass();
        $mapper->defaultTableNameTranslator = function ($class) {
            return null;
        };
        $table = $this->callProtected($mapper, 'getDefaultTable', $name);
        $this->assertEquals($result, $table);
    }
    
    /**
     * @group mapper
     * @covers Amiss\Mapper\Base::resolveUnnamedFields
     */
    public function testResolveUnnamedFieldsColumn()
    {
        $mapper = $this->getMockBuilder('Amiss\Mapper\Base')->getMockForAbstractClass();
        
        $mapper->unnamedPropertyTranslator = new TestPropertyTranslator; 
        
        $fields = array(
            'fooBar'=>array(),
            'fooBaz'=>array('name'=>''),
            'pants_yep'=>array(),
            'ahoy'=>array('name'=>'ahoy'),
            'ding'=>array('name'=>'dingdong'),
        );
        
        $expected = array(
            'fooBar'=>array('name'=>'field_fooBar'),
            'fooBaz'=>array('name'=>'field_fooBaz'),
            'pants_yep'=>array('name'=>'field_pants_yep'),
            'ahoy'=>array('name'=>'ahoy'),
            'ding'=>array('name'=>'dingdong'),
        );
        
        $found = $this->callProtected($mapper, 'resolveUnnamedFields', $fields);
        
        $this->assertEquals($expected, $found);
    }
    
    /**
     * @group mapper
     * @covers Amiss\Mapper\Note::determineTypeHandler
     * @dataProvider dataForDetermineTypeHandler
     */
    public function testDetermineTypeHandler($in, $out)
    {
        $mapper = $this->getMockBuilder('Amiss\Mapper\Base')->getMockForAbstractClass();
        $mapper->typeHandlers[$out] = $out;
        $found = $this->callProtected($mapper, 'determineTypeHandler', $in);
        $this->assertEquals($out, $found);
    }
    
    public function dataForDetermineTypeHandler()
    {
        return array(
            array('VARCHAR(80)', 'varchar'),
            array('VARCHAR (80) NOT NULL FOO BAR', 'varchar'),
            array('', ''),
            array('ID', 'id'),
            array('BZZ|BZZ', 'bzz'),
            array('  foo bar', 'foo'),
            array('|  foo bar', ''),
        );
    }
}

class TestPropertyTranslator implements \Amiss\Name\Translator
{
    public function translate(array $names)
    {
        $trans = array();
        foreach ($names as $n) {
            $trans[$n] = 'field_'.$n;
        }
        return $trans;
    }
    
    public function untranslate(array $names)
    {
        $trans = array();
        foreach ($names as $n) {
            $trans[$n] = substr($n, 6);
        }
        return $trans;
    }
}
