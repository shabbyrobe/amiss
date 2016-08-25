<?php
namespace Amiss\Test\Acceptance;

use \Amiss\Test\Helper\ClassBuilder;
use \Amiss\Demo;

/**
 * @group mapper
 * @group unit
 */ 
class NoteMapperTest extends \Amiss\Test\Helper\TestCase
{   
    /**
     * @covers Amiss\Mapper\Note::loadMeta
     */
    public function testGetMetaWithDefinedTable()
    {
        $mapper = new \Amiss\Mapper\Note;
        $class = ClassBuilder::i()->registerOne('
            /** :amiss = {"table": "custom_table"}; */
            class Test {}
        ');
        $meta = $mapper->getMeta($class);
        $this->assertEquals('custom_table', $meta->table);
    }

    /**
     * @covers Amiss\Mapper\Note::loadMeta
     */
    public function testGetMetaWithDefaultTable()
    {
        $mapper = $this->getMockBuilder('\Amiss\Mapper\Note')
            ->setMethods(array('getDefaultTable'))
            ->getMock()
        ;
        $mapper->expects($this->once())->method('getDefaultTable');
        $class = ClassBuilder::i()->registerOne('/** :amiss = true; */ class Test {}');

        $meta = $mapper->getMeta($class);
    }
    
    /**
     * @covers Amiss\Mapper\Note::loadMeta
     */
    public function testGetMetaCache()
    {
        $cacheData = array();
        $getCount = $setCount = 0;
        
        $cache = new \Amiss\Cache(
            function($key) use (&$cacheData, &$getCount) {
                ++$getCount;
                return isset($cacheData[$key]) ? $cacheData[$key] : null;
            },
            function($key, $value) use (&$cacheData, &$setCount) {
                ++$setCount;
                $cacheData[$key] = $value;
            }
        );
        
        $mapper = new \Amiss\Mapper\Note($cache);
        $this->assertCount(0, $cacheData);
        $meta = $mapper->getMeta(Demo\Artist::class);
        $this->assertCount(1, $cacheData);
        $this->assertEquals(1, $getCount);
        $this->assertEquals(1, $setCount);

        $mapper = new \Amiss\Mapper\Note($cache);
        $meta = $mapper->getMeta(Demo\Artist::class);
        $this->assertEquals(2, $getCount);
        $this->assertEquals(1, $setCount);
    }

    /**
     * @covers Amiss\Mapper\Note::loadMeta
     */
    public function testGetMetaMultiplePrimaries()
    {
        $mapper = new \Amiss\Mapper\Note;
        $class = ClassBuilder::i()->registerOne('
            /** :amiss = true; */
            class Test {
                /** :amiss = {"field": {"primary": true}}; */
                public $id1;

                /** :amiss = {"field": {"primary": true}}; */
                public $id2;
            }
        ');
        $meta = $mapper->getMeta($class);
        $this->assertEquals(array('id1', 'id2'), $meta->primary);
    }
    
    /**
     * @covers Amiss\Mapper\Note::loadMeta
     */
    public function testGetMetaFieldsFound()
    {
        $mapper = new \Amiss\Mapper\Note;
        $class = ClassBuilder::i()->registerOne('
            /** :amiss = true; */
            class Test {
                /** :amiss = {"field": true}; */ public $foo;
                /** :amiss = {"field": true}; */ public $bar;
            }
        ');
        $meta = $mapper->getMeta($class);
        $this->assertEquals(array('foo', 'bar'), array_keys($meta->fields));
    }
    
    /**
     * @covers Amiss\Mapper\Note::loadMeta
     */
    public function testGetMetaSkipsPropertiesWithNoFieldNote()
    {
        $mapper = new \Amiss\Mapper\Note;
        $class = ClassBuilder::i()->registerOne('
            /** :amiss = true; */
            class Test {
                public $notAField;
                
                /** :amiss = {"field": true}; */ public $yepAField;
            }
        ');
        $meta = $mapper->getMeta($class);
        $this->assertEquals(array('yepAField'), array_keys($meta->fields));
    }
    
    /**
     * @covers Amiss\Mapper\Note::loadMeta
     */
    public function testGetMetaGetterWithDefaultSetter()
    {
        $mapper = new \Amiss\Mapper\Note;
        $class = ClassBuilder::i()->registerOne('
            /** :amiss = true; */
            class Test {
                /** :amiss = {"field": true}; */
                public function getFoo(){}
                public function setFoo($value){} 
            }
        ');
        $meta = $mapper->getMeta($class);
        $expected = [
            'id'=>'foo',
            'name'=>'foo', 'type'=>['id'=>null], 'getter'=>'getFoo', 'setter'=>'setFoo',
            'required'=>false,
        ];
        $this->assertEquals($expected, $meta->fields['foo']);
    }

    /**
     * @covers Amiss\Mapper\Note::loadMeta
     */
    public function testGetMetaWithDefinedConstructor()
    {
        $mapper = new \Amiss\Mapper\Note;
        $class = ClassBuilder::i()->registerOne('
            /** :amiss = {"constructor": "pants"}; */
            class Test {}
        ');
        $meta = $mapper->getMeta($class);
        $this->assertEquals('pants', $meta->constructor);
    }

    /**
     * @covers Amiss\Mapper\Note::loadMeta
     */
    public function testGetMetaWithDefaultConstructor()
    {
        $mapper = new \Amiss\Mapper\Note;
        $class = ClassBuilder::i()->registerOne('
            /** :amiss = {"table": "pants"}; */
            class Test {}
        ');
        $meta = $mapper->getMeta($class);
        $this->assertEquals('__construct', $meta->constructor);
    }

    /**
     * @covers Amiss\Mapper\Note::loadMeta
     */
    public function testGetMetaPrimaryNoteImpliesFieldNote()
    {
        $mapper = new \Amiss\Mapper\Note;
        $class = ClassBuilder::i()->registerOne('
            /** :amiss = true; */
            class Test {
                /** :amiss = {"field": {"primary": true}}; */ public $id;
            }
        ');
        $meta = $mapper->getMeta($class);
        $this->assertEquals(array('id'), array_keys($meta->fields));
    }

    /**
     * @covers Amiss\Mapper\Note::loadMeta
     */
    public function testGetMetaPrimaryNoteImpliedFieldNoteAllowsType()
    {
        $mapper = new \Amiss\Mapper\Note;
        $class = ClassBuilder::i()->registerOne('
            /** :amiss = true; */
            class Test {
                /** :amiss = {"field": {"primary": true, "type": "autoinc"}}; */
                public $id;
            }
        ');
        $meta = $mapper->getMeta($class);
        $this->assertEquals(
            array(
                'id'=>array(
                    'id'=>'id',
                    'name'=>'id',
                    'type'=>array('id'=>'autoinc'),
                    'primary'=>true,
                    'required'=>false,
                )
            ),
            $meta->fields
        );
    }
    
    /**
     * @covers Amiss\Mapper\Note::loadMeta
     */
    public function testGetMetaPrimaryNoteFound()
    {
        $mapper = new \Amiss\Mapper\Note;
        $class = ClassBuilder::i()->registerOne('
            /** :amiss = true; */
            class Test {
                /** :amiss = {"field": {"primary": true}}; */ public $id;
            }
        ');
        $meta = $mapper->getMeta($class);
        $this->assertEquals(array('id'), $meta->primary);
    }

    /**
     * @covers Amiss\Mapper\Note::loadMeta
     */
    public function testGetMetaMultiPrimaryNoteFound()
    {
        $mapper = new \Amiss\Mapper\Note;
        $class = ClassBuilder::i()->registerOne('
            /** :amiss = true; */
            class Test {
                /** :amiss = {"field": {"primary": true}}; */ public $idPart1;
                /** :amiss = {"field": {"primary": true}}; */ public $idPart2;
            }
        ');
        $meta = $mapper->getMeta($class);
        $this->assertEquals(array('idPart1', 'idPart2'), $meta->primary);
    }
    
    /**
     * @covers Amiss\Mapper\Note::loadMeta
     */
    public function testGetMetaFieldTypeFound()
    {
        $mapper = new \Amiss\Mapper\Note;
        $class = ClassBuilder::i()->registerOne('
            /** :amiss = true; */
            class Test {
                /** :amiss = {"field": {"type": "foobar"}}; */
                public $id;
            }
        ');
        $meta = $mapper->getMeta($class);
        $field = $meta->fields['id'];
        $this->assertEquals(array('id'=>'foobar'), $field['type']);
    }

    public function testGetMetaClassNoteInheritance()
    {
        $mapper = new \Amiss\Mapper\Note;
        list ($ns, ) = ClassBuilder::i()->register('
            /** :amiss = {"fieldType": "string"}; */
            class Test1 {
                /** :amiss = {"field": true}; */
                public $foo;
            }

            class Test2 extends Test1 {}
        ');

        $meta2 = $mapper->getMeta("$ns\\Test2");
        $this->assertEquals(['id'=>'string'], $meta2->fieldType);
    }

    public function testGetMetaPropertyNoteInherits()
    {
        $mapper = new \Amiss\Mapper\Note;
        list ($ns,)  = ClassBuilder::i()->register('
            /** :amiss = true; */
            class Test1 {
                /** :amiss = {"field": true}; */
                public $foo;
            }
            class Test2 extends Test1 {
                public $foo;
            }
        ');
        
        $meta2 = $mapper->getMeta("$ns\\Test2");
        $this->assertArraySubset(['name'=>'foo', 'id'=>'foo'], $meta2->fields['foo']);
    }

    public function testGetMetaPropertyNoteMasking()
    {
        $mapper = new \Amiss\Mapper\Note;
        list ($ns,)  = ClassBuilder::i()->register('
            /** :amiss = true; */
            class Test1 {
                /** :amiss = {"field": true}; */
                public $foo;
            }
            class Test2 extends Test1 {
                /** :amiss = {"field": false}; */
                public $foo;
            }
        ');
        
        $meta2 = $mapper->getMeta("$ns\\Test2");
        $this->assertFalse(isset($meta2->fields['foo']));
    }

    public function testGetMetaPropertyRelationInherits()
    {
        $mapper = new \Amiss\Mapper\Note;
        list ($ns,)  = ClassBuilder::i()->register('
            /** :amiss = true; */
            class Test1 {
                /** :amiss = {"has": {"type": "pants"}}; */
                public $foo;
            }
            class Test2 extends Test1 {
                public $foo;
            }
        ');
        
        $meta2 = $mapper->getMeta("$ns\\Test2");
        $this->assertArraySubset(['pants', 'id' => 'foo'], $meta2->relations['foo']);
    }

    public function testGetMetaPropertyRelationMasking()
    {
        $mapper = new \Amiss\Mapper\Note;
        list ($ns,)  = ClassBuilder::i()->register('
            /** :amiss = true; */
            class Test1 {
                /** :amiss = {"has": {"type": "pants"}}; */
                public $foo;
            }
            class Test2 extends Test1 {
                /** :amiss = {"has": false}; */
                public $foo;
            }
        ');
        
        $meta2 = $mapper->getMeta("$ns\\Test2");
        $this->assertEmpty($meta2->relations);
    }

    /**
     * @covers Amiss\Mapper\Note::fillGetterSetter
     */
    public function testGetMetaRelationWithInferredGetterAndInferredSetter()
    {
        $mapper = new \Amiss\Mapper\Note;
        $class = ClassBuilder::i()->registerOne('
            /** :amiss = true; */
            class Foo {
                /** :amiss = {"field": {"primary": true}}; */
                public $id;

                /** :amiss = {"field": true}; */
                public $barId;

                private $bar;
                
                /** 
                 * :amiss = {"has": {
                 *     "type": "one",
                 *     "of"  : "Bar",
                 *     "from": "barId"
                 * }};
                 */
                public function getBar() { return $this->bar; }
            }
        ');
        $meta = $mapper->getMeta($class);
        $expected = array(
            'bar'=>array('one', 'of'=>"Bar", 'from'=>'barId', 'getter'=>'getBar', 'setter'=>'setBar', 'id'=>'bar', 'mode'=>'default'),
        );
        $this->assertEquals($expected, $meta->relations);
    }

    /**
     * @covers Amiss\Mapper\Note::loadMeta
     */
    public function testPrimaryFieldTranslation()
    {
        $class = ClassBuilder::i()->registerOne('
            /** :amiss = true; */
            class Foo {
                /** :amiss = {"field": {"primary": true}}; */
                public $fooBarBaz;

                /** :amiss = {"field": true}; */
                public $bazQuxDing;
            }
        ');

        $mapper = new \Amiss\Mapper\Note;
        $mapper->unnamedPropertyTranslator = new \Amiss\Name\CamelToUnderscore();
        $meta = $mapper->getMeta($class);
        
        $fields = $meta->fields;
        $this->assertEquals('foo_bar_baz',  $fields['fooBarBaz']['name']);
        $this->assertEquals('baz_qux_ding', $fields['bazQuxDing']['name']);
    }
    
    /**
     * @covers Amiss\Mapper\Note::fillGetterSetter
     * @dataProvider dataForInferSetter
     */
    public function testRelationFillGetterSetterInferSetter($prefix)
    {
        $class = ClassBuilder::i()->registerOne('
            /** :amiss = true; */
            class Foo'.$prefix.' {
                /** :amiss = {"has": {"type": "pants"}}; */
                function '.$prefix.'Pants() {}
            }
        ');
        $mapper = new \Amiss\Mapper\Note;
        $meta = $mapper->getMeta($class);
        $expected = [
            'pants'=>[
                'pants',
                'getter'=>"{$prefix}Pants",
                'setter'=>'setPants',
                'mode'=>'default',
                'id'=>'pants',
            ],
        ];
        $this->assertEquals($expected, $meta->relations);
    }

    function dataForInferSetter()
    {
        return [['has'], ['get'], ['is']];
    }

    /**
     * @covers Amiss\Mapper\Note::fillGetterSetter
     */
    public function testGetMetaRelationWithInferredGetterAndExplicitSetter()
    {
        $mapper = new \Amiss\Mapper\Note;
        $class = ClassBuilder::i()->registerOne('
            /** :amiss = true; */
            class Foo {
                /** :amiss = {"field": {"primary": true}}; */
                public $id;

                /** :amiss = {"field": true}; */
                public $barId;
                
                private $bar;
                
                /** 
                 * :amiss = {"has": {
                 *     "type"  : "one",
                 *     "of"    : "Bar",
                 *     "from"  : "barId",
                 *     "setter": "setLaDiDaBar"
                 * }};
                 */
                public function getBar()             { return $this->bar; }
                public function setLaDiDaBar($value) { $this->bar = $value; }
            }
        ');
        $meta = $mapper->getMeta($class);
        $expected = array(
            'bar'=>array('one', 'of'=>"Bar", 'from'=>'barId', 'getter'=>'getBar', 'setter'=>'setLaDiDaBar', 'id'=>'bar', 'mode'=>'default'),
        );
        $this->assertEquals($expected, $meta->relations);
    }
    
    /**
     * @covers Amiss\Mapper\Note::loadMeta
     */
    public function testGetMetaOneToManyPropertyRelationWithNoOn()
    {
        $mapper = new \Amiss\Mapper\Note;
        $class1 = ClassBuilder::i()->registerOne('
            /** :amiss = true; */
            class Class1 {
                /** :amiss = {"field": {"primary": true}}; */
                public $class1id;
                
                /** :amiss = {"field": true}; */
                public $class2Id;
                
                /** :amiss = {"has": {"type": "many", "of": "Class2"}}; */
                public $class2;
            }
        ');
        $class2 = ClassBuilder::i()->registerOne('
            /** :amiss = true; */
            class Class2 {
                /** :amiss = {"field": {"primary": true}}; */
                public $class2Id;
            }
        ');
        $meta = $mapper->getMeta($class1);
        $expected = array(
            'class2'=>array('many', 'of'=>"Class2", 'id'=>'class2', 'mode'=>'default')
        );
        $this->assertEquals($expected, $meta->relations);
    }
    
    /**
     * @covers Amiss\Mapper\Note::loadMeta
     */
    public function testGetMetaWithStringRelation()
    {
        $mapper = new \Amiss\Mapper\Note;
        $name = ClassBuilder::i()->registerOne('
            /** :amiss = true; */
            class Class1 {
                /** :amiss = {"has": "test"}; */
                public $test;
            }
        ');
        $meta = $mapper->getMeta($name);
        $expected = array(
            'test'=>array('test', 'id'=>'test', 'mode'=>'default')
        );
        $this->assertEquals($expected, $meta->relations);
    }

    public function testGetMetaWithClassIndex()
    {
        $mapper = new \Amiss\Mapper\Note;
        $name = ClassBuilder::i()->registerOne('
            /** :amiss = {"indexes": {"foo": {"fields": ["a"]}}}; */
            class Test {
                /** :amiss = {"field": true}; */
                public $a;
            }
        ');
        $meta = $mapper->getMeta($name);
        $expected = ['foo'=>['fields'=>['a'], 'key'=>false]];
        $this->assertEquals($expected, $meta->indexes);
    }

    public function testGetMetaWithClassKeyIndex()
    {
        $mapper = new \Amiss\Mapper\Note;
        $name = ClassBuilder::i()->registerOne('
            /** 
             * :amiss = {
             *     "indexes": {
             *        "foo": {"fields": ["a"], "key": true}
             *     }
             * };
             */
            class Test {
                /** :amiss = {"field": true}; */
                public $a;
            }
        ');
        $meta = $mapper->getMeta($name);
        $expected = ['foo'=>['fields'=>['a'], 'key'=>true]];
        $this->assertEquals($expected, $meta->indexes);
    }

    public function testGetMetaWithMultiFieldClassIndex()
    {
        $mapper = new \Amiss\Mapper\Note;
        $name = ClassBuilder::i()->registerOne('
            /** 
             * :amiss = {"indexes": {
             *    "foo": {"fields": ["b", "a"]}
             * }};
             */
            class Test {
                /** :amiss = {"field": true}; */
                public $a;
            }
        ');
        $meta = $mapper->getMeta($name);
        $expected = ['foo'=>['fields'=>['b', 'a'], 'key'=>false]];
        $this->assertEquals($expected, $meta->indexes);
    }

    public function testGetMetaWithDuplicateIndexDefinition()
    {
        $mapper = new \Amiss\Mapper\Note;
        $name = ClassBuilder::i()->registerOne('
            /** 
             * :amiss = {"indexes": {"a": {"fields": ["a"], "key": true}}};
             */
            class Test {
                /** :amiss = {"field": {"index": true}}; */
                 public $a;
            }
        ');
        $this->setExpectedException(\Amiss\Exception::class, "Duplicate index name 'a'");
        $meta = $mapper->getMeta($name);
    }

    public function testGetMetaWithStringFieldIndexFails()
    {
        $mapper = new \Amiss\Mapper\Note;
        $name = ClassBuilder::i()->registerOne('
            /** :amiss = true; */
            class Test {
                /** :amiss = {"field": {"index": "foo"}}; */
                public $a;
            }
        ');
        $this->setExpectedException(\Amiss\Exception::class, "Invalid index 'a': index must either be boolean or an array of index metadata");
        $meta = $mapper->getMeta($name);
    }

    public function testGetMetaWithStringFieldKey()
    {
        $mapper = new \Amiss\Mapper\Note;
        $name = ClassBuilder::i()->registerOne('
            /** :amiss = true; */
            class Test {
                /** :amiss = {"field": {"index": {"key": true}}}; */
                public $a;
            }
        ');
        $meta = $mapper->getMeta($name);
        $expected = ['a'=>['fields'=>['a'], 'key'=>true]];
        $this->assertEquals($expected, $meta->indexes);
    }

    public function testGetMetaAutoNamedIndexFromGetter()
    {
        $mapper = new \Amiss\Mapper\Note;
        $name = ClassBuilder::i()->registerOne('
            /** :amiss = true; */
            class Test {
                private $field;
                
                /** :amiss = {"field": {"index": true}}; */
                public function getField()   { return $this->field; }
                public function setField($v) { $this->field = $v;   }
            }
        ');
        $meta = $mapper->getMeta($name);

        $expected = [
            'field'=>['fields'=>['field'], 'key'=>false],
        ];
        $this->assertEquals($expected, $meta->indexes);
    }

    public function testGetMetaAutoNamedKeyFromGetter()
    {
        $mapper = new \Amiss\Mapper\Note;
        $name = ClassBuilder::i()->registerOne('
            /** :amiss = true; */
            class Test {
                private $field;
                
                /** :amiss = {"field": {"index": {"key": true}}}; */
                public function getField()   { return $this->field; }
                public function setField($v) { $this->field = $v;   }
            }
        ');
        $meta = $mapper->getMeta($name);

        $expected = [
            'field'=>['fields'=>['field'], 'key'=>true],
        ];
        $this->assertEquals($expected, $meta->indexes);
    }

    public function testGetMetaAutoNamedIndexFromField()
    {
        $mapper = new \Amiss\Mapper\Note;
        $name = ClassBuilder::i()->registerOne('
            /** :amiss = true; */
            class Test {
                /** :amiss = {"field": {"index": true}}; */
                public $field;
            }
        ');
        $meta = $mapper->getMeta($name);

        $expected = [
            'field'=>['fields'=>['field'], 'key'=>false],
        ];
        $this->assertEquals($expected, $meta->indexes);
    }

    public function testGetMetaAutoNamedKeyFromField()
    {
        $mapper = new \Amiss\Mapper\Note;
        $name = ClassBuilder::i()->registerOne('
            /** :amiss = true; */
            class Test {
                /** :amiss = {"field": {"index": {"key": true}}}; */
                public $field;
            }
        ');
        $meta = $mapper->getMeta($name);

        $expected = [
            'field'=>['fields'=>['field'], 'key'=>true],
        ];
        $this->assertEquals($expected, $meta->indexes);
    }

    public function testGetMetaPrimaryAutoFieldNameFromMethod()
    {
        $mapper = new \Amiss\Mapper\Note;
        $name = ClassBuilder::i()->registerOne('
            /** :amiss = true; */
            class Test {
                private $field;
                /** :amiss = {"field": {"primary": true}}; */
                public function getField() { return $this->field; }
                public function setField($v) { $this->field = $v; }
            }
        ');
        $meta = $mapper->getMeta($name);

        $expected = ['field'];
        $this->assertEquals($expected, $meta->primary);
    }

    public function testGetMetaConstructor()
    {
        $mapper = new \Amiss\Mapper\Note;

        $name = ClassBuilder::i()->registerOne('
            /** :amiss = true; */
            class Test {
                /** :amiss = {"constructor": true}; */
                public static function foo() {}
            }
        ');
        $meta = $mapper->getMeta($name);
        $this->assertEquals('foo', $meta->constructor);
    }

    public function testGetMetaConstructorArg()
    {
        $mapper = new \Amiss\Mapper\Note;

        $name = ClassBuilder::i()->registerOne('
            /** :amiss = true; */
            class Test {
                /**
                 * :amiss = {"constructor": [
                 *      ["relation", "pants"]
                 * ]};
                 */
                public static function foo() {}
            }
        ');
        $meta = $mapper->getMeta($name);
        $this->assertEquals('foo', $meta->constructor);
        $this->assertEquals([['relation', 'pants']], $meta->constructorArgs);
    }

    public function testGetMetaDefaultConstructorArg()
    {
        $mapper = new \Amiss\Mapper\Note;

        $name = ClassBuilder::i()->registerOne('
            /** :amiss = true; */
            class Test {
                /**
                 * :amiss = {"constructor": [
                 *     ["relation", "pants"]
                 * ]};
                 */
                public function __construct()
                {}
            }
        ');
        $meta = $mapper->getMeta($name);
        $this->assertEquals('__construct', $meta->constructor);
        $this->assertEquals([['relation', 'pants']], $meta->constructorArgs);
    }

    public function testGetMetaConstructorArgs()
    {
        $mapper = new \Amiss\Mapper\Note;

        $name = ClassBuilder::i()->registerOne('
            /** :amiss = true; */
            class Test {
                /**
                 * :amiss = {"constructor": [
                 *     ["relation", "pants"],
                 *     ["field"   , "foo"]
                 * ]};
                 */
                public static function foo($a, $b) {}
            }
        ');
        $meta = $mapper->getMeta($name);
        $this->assertEquals('foo', $meta->constructor);
        $this->assertEquals([['relation', 'pants'], ['field', 'foo']], $meta->constructorArgs);
    }

    public function testGetMetaFieldWithStringName()
    {
        $mapper = new \Amiss\Mapper\Note;

        $name = ClassBuilder::i()->registerOne('
            /** :amiss = true; */
            class Test {
                /** :amiss = {"field": "bar"}; */
                public $foo;
            }
        ');
        $meta = $mapper->getMeta($name);
        $expected = ['id'=>'foo', 'name'=>'bar', 'type'=>['id'=>null], 'required'=>false];
        $this->assertEquals($expected, $meta->fields['foo']);
    }

    public function testClassRelations()
    {
        $mapper = new \Amiss\Mapper\Note;
        $name = ClassBuilder::i()->registerOne('
            /**
             * :amiss = {"relations": {
             *     "foo": {
             *         "type": "one",
             *         "of"  : "Pants"
             *     }
             * }};
             */
            class Test {
            }
        ');
        $meta = $mapper->getMeta($name);
        $expected = ['foo'=>[
            'one', 'of'=>'Pants', 'mode'=>'class', 'id'=>'foo',
        ]];
        $this->assertEquals($expected, $meta->relations);
    }

    public function testCanMap()
    {
        $mapper = new \Amiss\Mapper\Note();
        $name = ClassBuilder::i()->registerOne('
            /** :amiss = true; */
            class Test {}
        ');
        $this->assertTrue($mapper->canMap($name));
    }

    public function testCanMapUnmapped()
    {
        $mapper = new \Amiss\Mapper\Note();
        $name = ClassBuilder::i()->registerOne('
            class Test {}
        ');
        $this->assertFalse($mapper->canMap($name));
    }
}
