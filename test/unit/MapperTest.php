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
        $mapper->fromObjects(array('a', 'b'), null, 'foo');
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
        $mapper->fromObjects(null, null, 'foo');
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
        $row = $mapper->fromObject($obj, $meta);
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
        $row = $mapper->fromObject($obj, $meta);
        $this->assertEquals($input, $row);
    }

    /**
     * @covers Amiss\Mapper\Base::toObjects
     */
    public function testToObjects()
    {
        $mapper = $this->getMockBuilder('Amiss\Mapper\Base')
            ->setMethods(array('toObject', 'getMeta'))
            ->getMockForAbstractClass()
        ;
        $mapper->expects($this->exactly(2))->method('toObject');
        $meta = new \Amiss\Meta('a', 'b', []);
        $mapper->expects($this->any())->method('getMeta')->will($this->returnValue($meta));
        $mapper->toObjects(array('a', 'b'), null, 'foo');
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
     * @covers Amiss\Mapper\Base::determineTypeHandler
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

    /**
     * @covers Amiss\Mapper::createObject
     * @group constructor
     */
    public function testCreateObject()
    {
        $mapper = $this->getMockBuilder('Amiss\Mapper\Base')->getMockForAbstractClass();
        $meta = new \Amiss\Meta('stdClass', 'test_table', [
            'fields'=>[
                'a'=>['name'=>'a', 'type'=>'string'], 'b'=>['name'=>'b', 'type'=>'string'],
            ]
        ]);
        $obj = $mapper->toObject(['a'=>'foo', 'b'=>'bar'], null, $meta);
        $this->assertInstanceOf('stdClass', $obj);
        $this->assertEquals('foo', $obj->a);
        $this->assertEquals('bar', $obj->b);
    }

    /**
     * @covers Amiss\Mapper::createObject
     * @group constructor
     */
    public function testCreateObjectPropertyArgs()
    {
        $mapper = $this->getMockBuilder('Amiss\Mapper\Base')->getMockForAbstractClass();
        $meta = new \Amiss\Meta(__NAMESPACE__.'\TestCreateObject', 'test_table', [
            'fields'=>[
                'a'=>['name'=>'a', 'type'=>'string'], 'b'=>['name'=>'b', 'type'=>'string'],
            ],
            'constructorArgs'=>[
                ['property', 'b'],
                ['property', 'a'],
            ],
        ]);
        $obj = $mapper->toObject(['a'=>'foo', 'b'=>'bar'], null, $meta);
        $this->assertEquals(['bar', 'foo'], $obj->args);
        $this->assertFalse(isset($obj->a));
        $this->assertFalse(isset($obj->b));
    }

    /**
     * @covers Amiss\Mapper::createObject
     * @group constructor
     */
    public function testCreateObjectArgs()
    {
        $mapper = $this->getMockBuilder('Amiss\Mapper\Base')->getMockForAbstractClass();
        $meta = new \Amiss\Meta(__NAMESPACE__.'\TestCreateObject', 'test_table', [
            'fields'=>[
                'a'=>['name'=>'a', 'type'=>'string'], 'b'=>['name'=>'b', 'type'=>'string'],
            ],
        ]);
        $obj = $mapper->toObject(['a'=>'foo', 'b'=>'bar'], ['bar', 'foo'], $meta);
        $this->assertEquals(['bar', 'foo'], $obj->args);
        $this->assertEquals('foo', $obj->a);
        $this->assertEquals('bar', $obj->b);
    }

    /**
     * @covers Amiss\Mapper::createObject
     * @group constructor
     */
    public function testCreateObjectMixedArgs()
    {
        $mapper = $this->getMockBuilder('Amiss\Mapper\Base')->getMockForAbstractClass();
        $meta = new \Amiss\Meta(__NAMESPACE__.'\TestCreateObject', 'test_table', [
            'fields'=>[
                'a'=>['name'=>'a', 'type'=>'string'], 'b'=>['name'=>'b', 'type'=>'string'],
            ],
            'constructorArgs'=>[
                ['arg', 1],
                ['property', 'b'],
                ['arg', 0],
            ],
        ]);
        $obj = $mapper->toObject(['a'=>'foo', 'b'=>'bar'], ['baz', 'qux'], $meta);
        $this->assertEquals(['qux', 'bar', 'baz'], $obj->args);
        $this->assertEquals('foo', $obj->a);
        $this->assertFalse(isset($obj->b));
    }

    /**
     * @covers Amiss\Mapper::createObject
     * @group constructor
     */
    public function testCreateObjectArgsNull()
    {
        $mapper = $this->getMockBuilder('Amiss\Mapper\Base')->getMockForAbstractClass();
        $meta = new \Amiss\Meta(__NAMESPACE__.'\TestCreateObject', 'test_table', [
            'fields'=>[
                'a'=>['name'=>'a', 'type'=>'string'], 'b'=>['name'=>'b', 'type'=>'string'],
            ],
            'constructorArgs'=>[
                ['arg', 1],
                ['arg', 0],
            ],
        ]);
        $obj = $mapper->toObject(['a'=>'foo', 'b'=>'bar'], [null, 'qux'], $meta);
        $this->assertEquals(['qux', null], $obj->args);
    }

    /**
     * @covers Amiss\Mapper::createObject
     * @group constructor
     */
    public function testCreateObjectPropertyNull()
    {
        $mapper = $this->getMockBuilder('Amiss\Mapper\Base')->getMockForAbstractClass();
        $meta = new \Amiss\Meta(__NAMESPACE__.'\TestCreateObject', 'test_table', [
            'fields'=>[
                'a'=>['name'=>'a', 'type'=>'string'], 'b'=>['name'=>'b', 'type'=>'string'],
            ],
            'constructorArgs'=>[
                ['property', 'b'],
                ['property', 'a'],
            ],
        ]);
        $obj = $mapper->toObject(['a'=>null, 'b'=>'bar'], null, $meta);
        $this->assertEquals(['bar', null], $obj->args);
        $this->assertFalse(isset($obj->a));
        $this->assertFalse(isset($obj->b));
    }

    /**
     * @covers Amiss\Mapper::createObject
     * @group constructor
     */
    public function testCreateObjectRelations()
    {
        $mapper = $this->getMockBuilder('Amiss\Mapper\Base')->getMockForAbstractClass();
        $meta = new \Amiss\Meta(__NAMESPACE__.'\TestCreateObject', 'test_table', [
            'fields'=>[
                'a'=>['name'=>'a', 'type'=>'string'], 'b'=>['name'=>'b', 'type'=>'string'],
            ],
            'relations'=>[
                'rel1'=>['one', 'from'=>'foo'],
                'rel2'=>['one', 'from'=>'bar'],
            ],
            'constructorArgs'=>[
                ['property', 'rel1'],
                ['property', 'rel2'],
            ],
        ]);
        $obj = $mapper->toObject(['a'=>'foo', 'b'=>'bar', 'rel1'=>'yep', 'rel2'=>'woo'], null, $meta);
        $this->assertEquals(['yep', 'woo'], $obj->args);
        $this->assertEquals('foo', $obj->a);
        $this->assertEquals('bar', $obj->b);
        $this->assertFalse(isset($obj->rel1));
        $this->assertFalse(isset($obj->rel2));
    }

    /**
     * @covers Amiss\Mapper::createObject
     * @group constructor
     */
    public function testCreateObjectDefaultConstructor()
    {
        $mapper = $this->getMockBuilder('Amiss\Mapper\Base')->getMockForAbstractClass();
        $class = __NAMESPACE__.'\TestCreateObject';
        $meta = new \Amiss\Meta($class, 'test_table', [
            'fields'=>['a'=>['name'=>'a', 'type'=>'string']]
        ]);
        $obj = $mapper->toObject(['a'=>'foo'], null, $meta);
        $this->assertInstanceOf($class, $obj);
        $this->assertEquals('foo', $obj->a);
        $this->assertTrue($obj->constructCalled);
        $this->assertFalse($obj->staticConstructCalled);
    }

    /**
     * @covers Amiss\Mapper::createObject
     * @group constructor
     */
    public function testCreateObjectStaticConstructor()
    {
        $mapper = $this->getMockBuilder('Amiss\Mapper\Base')->getMockForAbstractClass();
        $class = __NAMESPACE__.'\TestCreateObject';
        $meta = new \Amiss\Meta($class, 'test_table', [
            'fields'=>['a'=>['name'=>'a', 'type'=>'string']],
            'constructor'=>'staticConstruct',
        ]);
        $obj = $mapper->toObject(['a'=>'foo'], null, $meta);
        $this->assertInstanceOf($class, $obj);
        $this->assertEquals('foo', $obj->a);
        $this->assertTrue($obj->constructCalled);
        $this->assertTrue($obj->staticConstructCalled);
    }
}

class TestCreateObject
{
    public $constructCalled = false;
    public $staticConstructCalled = false;
    public $args;

    public function __construct()
    {
        $this->constructCalled = true;
        $this->args = func_get_args();
    }

    public static function staticConstruct()
    {
        $o = new static;
        $o->staticConstructCalled = true;
        $o->args = func_get_args();
        return $o;
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
