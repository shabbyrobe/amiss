<?php
namespace Amiss\Test\Acceptance;

/**
 * @group mapper
 * @group unit
 */ 
class NoteMapperTest extends \CustomTestCase
{   
    /**
     * @covers Amiss\Mapper\Note::loadMeta
     */
    public function testGetMetaWithDefinedTable()
    {
        $mapper = new \Amiss\Mapper\Note;
        $class = $this->createFnScopeClass('Test', "
            /** @table custom_table */
            class Test {}
        ");
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
        $class = $this->createFnScopeClass('Test', "class Test {}");
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
        
        $this->assertArrayNotHasKey('stdClass', $cacheData);
        $meta = $mapper->getMeta('stdClass');
        $this->assertArrayHasKey('stdClass', $cacheData);
        $this->assertEquals(1, $getCount);
        $this->assertEquals(1, $setCount);
        
        $mapper = new \Amiss\Mapper\Note($cache);
        $meta = $mapper->getMeta('stdClass');
        $this->assertEquals(2, $getCount);
        $this->assertEquals(1, $setCount);
    }

    /**
     * @covers Amiss\Mapper\Note::loadMeta
     */
    public function testGetMetaMultiplePrimaries()
    {
        $mapper = new \Amiss\Mapper\Note;
        $class = $this->createFnScopeClass('Test', '
            class Test {
                /** @primary */ public $id1;
                /** @primary */ public $id2;
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
        $class = $this->createFnScopeClass('Test', '
            class Test {
                /** @field */ public $foo;
                /** @field */ public $bar;
            }
        ');
        
        $meta = $mapper->getMeta($class);
        $this->assertEquals(array('foo', 'bar'), array_keys($meta->getFields()));
    }
    
    /**
     * @covers Amiss\Mapper\Note::loadMeta
     */
    public function testGetMetaSkipsPropertiesWithNoFieldNote()
    {
        $mapper = new \Amiss\Mapper\Note;
        $class = $this->createFnScopeClass('Test', '
            class Test {
                public $notAField;
                
                /** @field */ public $yepAField;
            }
        ');
        $meta = $mapper->getMeta($class);
        $this->assertEquals(array('yepAField'), array_keys($meta->getFields()));
    }
    
    /**
     * @covers Amiss\Mapper\Note::loadMeta
     */
    public function testGetMetaGetterWithDefaultSetter()
    {
        $mapper = new \Amiss\Mapper\Note;
        $class = $this->createFnScopeClass('Test', '
            class Test {
                /** @field */
                public function getFoo(){}
                public function setFoo($value){} 
            }
        ');
        $meta = $mapper->getMeta($class);
        $expected = array('name'=>'foo', 'type'=>null, 'getter'=>'getFoo', 'setter'=>'setFoo');
        $this->assertEquals($expected, $meta->getField('foo'));
    }

    /**
     * @covers Amiss\Mapper\Note::loadMeta
     */
    public function testGetMetaWithDefinedConstructor()
    {
        $mapper = new \Amiss\Mapper\Note;
        $class = $this->createFnScopeClass('Test', "
            /** @constructor pants */
            class Test {}
        ");
        $meta = $mapper->getMeta($class);
        $this->assertEquals('pants', $meta->constructor);
    }

    /**
     * @covers Amiss\Mapper\Note::loadMeta
     */
    public function testGetMetaWithDefaultConstructor()
    {
        $mapper = new \Amiss\Mapper\Note;
        $class = $this->createFnScopeClass('Test', "
            /** @table pants */
            class Test {}
        ");
        $meta = $mapper->getMeta($class);
        $this->assertEquals('__construct', $meta->constructor);
    }

    /**
     * @covers Amiss\Mapper\Note::loadMeta
     */
    public function testGetMetaPrimaryNoteImpliesFieldNote()
    {
        $mapper = new \Amiss\Mapper\Note;
        $class = $this->createFnScopeClass('Test', '
            class Test {
                /** @primary */ public $id;
            }
        ');
        $meta = $mapper->getMeta($class);
        $this->assertEquals(array('id'), array_keys($meta->getFields()));
    }

    /**
     * @covers Amiss\Mapper\Note::loadMeta
     */
    public function testGetMetaPrimaryNoteImpliedFieldNoteAllowsType()
    {
        $mapper = new \Amiss\Mapper\Note;
        $class = $this->createFnScopeClass('Test', '
            class Test {
                /**
                 * @primary
                 * @type autoinc 
                 */ 
                public $id;
            }
        ');
        $meta = $mapper->getMeta($class);
        $this->assertEquals(array('id'=>array('name'=>'id', 'type'=>array('id'=>'autoinc'))), $meta->getFields());
    }
    
    /**
     * @covers Amiss\Mapper\Note::loadMeta
     */
    public function testGetMetaPrimaryNoteFound()
    {
        $mapper = new \Amiss\Mapper\Note;
        $class = $this->createFnScopeClass('Test', '
            class Test {
                /** @primary */ public $id;
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
        $class = $this->createFnScopeClass('Test', '
            class Test {
                /** @primary */ public $idPart1;
                /** @primary */ public $idPart2;
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
        $class = $this->createFnScopeClass('Test', '
            class Test {
                /** 
                 * @field
                 * @type foobar
                 */
                 public $id;
            }
        ');
        $meta = $mapper->getMeta($class);
        $field = $meta->getField('id');
        $this->assertEquals(array('id'=>'foobar'), $field['type']);
    }

    /**
     * @covers Amiss\Mapper\Note::loadMeta
     */
    public function testGetMetaWithParentClass()
    {
        $mapper = new \Amiss\Mapper\Note;
        $class1 = $this->createFnScopeClass("Test1", '
            class Test1 {
                /** @field */ public $foo;
            }
        ');
        $class2 = $this->createFnScopeClass("Test2", '
            class Test2 extends Test1 {
                /** @field */ public $bar;
            }
        ');
        
        $meta1 = $mapper->getMeta($class1);
        $meta2 = $mapper->getMeta($class2);
        $this->assertEquals($meta1, $this->getProtected($meta2, 'parent'));
    }

    /**
     * @covers Amiss\Mapper\Note::buildRelations
     * @covers Amiss\Mapper\Note::findGetterSetter
     */
    public function testGetMetaRelationWithInferredGetterAndInferredSetter()
    {
        $mapper = new \Amiss\Mapper\Note;
        $class = $this->createFnScopeClass('Foo', '
            class Foo {
                /** @primary */ public $id;
                /** @field */   public $barId;

                private $bar;
                
                /** 
                 * @has.one.of Bar
                 * @has.one.from barId
                 */
                public function getBar() { return $this->bar; }
            }
        ');
        $meta = $mapper->getMeta($class);
        $expected = array(
            'bar'=>array('one', 'of'=>"Bar", 'from'=>'barId', 'getter'=>'getBar', 'setter'=>'setBar'),
        );
        $this->assertEquals($expected, $meta->relations);
    }

    /**
     * @covers Amiss\Mapper\Note::loadMeta
     */
    public function testPrimaryFieldTranslation()
    {
        $class = $this->createFnScopeClass('Foo', "
            class Foo {
                /** @primary */
                public \$fooBarBaz;

                /** @field */
                public \$bazQuxDing;
            }
        ");

        $mapper = new \Amiss\Mapper\Note;
        $mapper->unnamedPropertyTranslator = new \Amiss\Name\CamelToUnderscore();
        $meta = $mapper->getMeta($class);
        
        $fields = $meta->getFields();
        $this->assertEquals('foo_bar_baz',  $fields['fooBarBaz']['name']);
        $this->assertEquals('baz_qux_ding', $fields['bazQuxDing']['name']);
    }

    /**
     * @covers Amiss\Mapper\Note::buildRelations
     * @covers Amiss\Mapper\Note::findGetterSetter
     */
    public function testGetMetaRelationWithInferredGetterAndExplicitSetter()
    {
        $mapper = new \Amiss\Mapper\Note;
        $class = $this->createFnScopeClass('Foo', '
            class Foo {
                /** @primary */ public $id;
                /** @field */   public $barId;
                
                private $bar;
                
                /** 
                 * @has.one.of Bar
                 * @has.one.from barId
                 * @setter setLaDiDaBar
                 */
                public function getBar()             { return $this->bar; }
                public function setLaDiDaBar($value) { $this->bar = $value; }
            }
        ');
        $meta = $mapper->getMeta($class);
        $expected = array(
            'bar'=>array('one', 'of'=>"Bar", 'from'=>'barId', 'getter'=>'getBar', 'setter'=>'setLaDiDaBar'),
        );
        $this->assertEquals($expected, $meta->relations);
    }
    
    /**
     * @covers Amiss\Mapper\Note::loadMeta
     * @covers Amiss\Mapper\Note::buildRelations
     */
    public function testGetMetaOneToManyPropertyRelationWithNoOn()
    {
        $mapper = new \Amiss\Mapper\Note;
        $class1 = $this->createFnScopeClass("Class1", "
            class Class1 {
                /** @primary */ 
                public \$class1id;
                
                /** @field */ 
                public \$class2Id;
                
                /** @has.many.of Class2 */
                public \$class2;
            }
        ");
        $class2 = $this->createClass("Class2", "
            class Class2 {
                /** @primary */ 
                public \$class2Id;
            }
        ");
        $meta = $mapper->getMeta($class1);
        $expected = array(
            'class2'=>array('many', 'of'=>"Class2")
        );
        $this->assertEquals($expected, $meta->relations);
    }
    
    /**
     * @covers Amiss\Mapper\Note::loadMeta
     * @covers Amiss\Mapper\Note::buildRelations
     */
    public function testGetMetaWithStringRelation()
    {
        $mapper = new \Amiss\Mapper\Note;
        $name = $this->createFnScopeClass("Class1", '
            class Class1 {
                /** @has test */ 
                public $test;
            }
        ');
        $meta = $mapper->getMeta($name);
        $expected = array(
            'test'=>array('test')
        );
        $this->assertEquals($expected, $meta->relations);
    }

    public function testGetMetaWithClassIndex()
    {
        $mapper = new \Amiss\Mapper\Note;
        $name = $this->createFnScopeClass("Class1", '
            class Class1 {
                /** @has test */ 
                public $test;
            }
        ');
        $meta = $mapper->getMeta($name);
        $expected = array(
            'test'=>array('test')
        );
        $this->assertEquals($expected, $meta->relations);
    }
}
