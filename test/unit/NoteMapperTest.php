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
        $class = __NAMESPACE__.'\\'.__FUNCTION__;
        $this->createClass($class, "
            namespace ".__NAMESPACE__.";
            /** @table custom_table */
            class ".__FUNCTION__." {}
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
        
        $class = __NAMESPACE__.'\\'.__FUNCTION__;
        $this->createClass($class, "
            namespace ".__NAMESPACE__.";
            class ".__FUNCTION__." {}
        ");
        
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
        $class = __NAMESPACE__.'\\'.__FUNCTION__;
        $this->createClass($class, '
            namespace '.__NAMESPACE__.';
            class '.__FUNCTION__.' {
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
        $class = __NAMESPACE__.'\\'.__FUNCTION__;
        $this->createClass($class, '
            namespace '.__NAMESPACE__.';
            class '.__FUNCTION__.' {
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
        $class = __NAMESPACE__.'\\'.__FUNCTION__;
        $this->createClass($class, '
            namespace '.__NAMESPACE__.';
            class '.__FUNCTION__.' {
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
        $class = __NAMESPACE__.'\\'.__FUNCTION__;
        $this->createClass($class, '
            namespace '.__NAMESPACE__.';
            class '.__FUNCTION__.' {
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
        $class = __NAMESPACE__.'\\'.__FUNCTION__;
        $this->createClass($class, "
            namespace ".__NAMESPACE__.";
            /** @constructor pants */
            class ".__FUNCTION__." {}
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
        $class = __NAMESPACE__.'\\'.__FUNCTION__;
        $this->createClass($class, "
            namespace ".__NAMESPACE__.";
            /** @table pants */
            class ".__FUNCTION__." {}
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
        $class = __NAMESPACE__.'\\'.__FUNCTION__;
        $this->createClass($class, '
            namespace '.__NAMESPACE__.';
            class '.__FUNCTION__.' {
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
        $class = __NAMESPACE__.'\\'.__FUNCTION__;
        $this->createClass($class, '
            namespace '.__NAMESPACE__.';
            class '.__FUNCTION__.' {
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
        $class = __NAMESPACE__.'\\'.__FUNCTION__;
        $this->createClass($class, '
            namespace '.__NAMESPACE__.';
            class '.__FUNCTION__.' {
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
        $class = __NAMESPACE__.'\\'.__FUNCTION__;
        $this->createClass($class, '
            namespace '.__NAMESPACE__.';
            class '.__FUNCTION__.' {
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
        $class = __NAMESPACE__.'\\'.__FUNCTION__;
        $this->createClass($class, '
            namespace '.__NAMESPACE__.';
            class '.__FUNCTION__.' {
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
        $prefix = __NAMESPACE__.'\\'.__FUNCTION__;
        $this->createClass("{$prefix}1", '
            namespace '.__NAMESPACE__.';
            class '.__FUNCTION__.'1 {
                /** @field */ public $foo;
            }
        ');
        $this->createClass("{$prefix}2", '
            namespace '.__NAMESPACE__.';
            class '.__FUNCTION__.'2 extends '.__FUNCTION__.'1 {
                /** @field */ public $bar;
            }
        ');
        
        $meta1 = $mapper->getMeta("{$prefix}1");
        $meta2 = $mapper->getMeta("{$prefix}2");
        $this->assertEquals($meta1, $this->getProtected($meta2, 'parent'));
    }

    /**
     * @covers Amiss\Mapper\Note::buildRelations
     * @covers Amiss\Mapper\Note::findGetterSetter
     */
    public function testGetMetaRelationWithInferredGetterAndInferredSetter()
    {
        $mapper = new \Amiss\Mapper\Note;
        $name = __FUNCTION__;
        $class = __NAMESPACE__."\\{$name}Foo";
        $this->createClass($class, "
            namespace ".__NAMESPACE__.";
            class {$name}Foo {
                /** @primary */ 
                public \$id;
                /** @field */
                public \$barId;
                
                private \$bar;
                
                /** 
                 * @has.one.of {$name}Bar
                 * @has.one.on barId
                 */
                public function getBar()
                {
                    return \$this->bar;
                }
            }
        ");
        $meta = $mapper->getMeta($class);
        $expected = array(
            'bar'=>array('one', 'of'=>$name."Bar", 'on'=>'barId', 'getter'=>'getBar', 'setter'=>'setBar'),
        );
        $this->assertEquals($expected, $meta->relations);
    }

    /**
     * @covers Amiss\Mapper\Note::loadMeta
     */
    public function testPrimaryFieldTranslation()
    {
        $name = __FUNCTION__;
        $class = __NAMESPACE__."\\{$name}Foo";
        $this->createClass($class, "
            namespace ".__NAMESPACE__.";
            class {$name}Foo {
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
        $name = __FUNCTION__;
        $class = __NAMESPACE__."\\{$name}Foo";
        $this->createClass($class, "
            namespace ".__NAMESPACE__.";
            class {$name}Foo {
                /** @primary */ 
                public \$id;
                /** @field */
                public \$barId;
                
                private \$bar;
                
                /** 
                 * @has.one.of {$name}Bar
                 * @has.one.on barId
                 * @setter setLaDiDaBar
                 */
                public function getBar()
                {
                    return \$this->bar;
                }
                
                public function setLaDiDaBar(\$value)
                {
                    \$this->bar = \$value;
                }
            }
        ");
        $meta = $mapper->getMeta($class);
        $expected = array(
            'bar'=>array('one', 'of'=>$name."Bar", 'on'=>'barId', 'getter'=>'getBar', 'setter'=>'setLaDiDaBar'),
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
        $name = __FUNCTION__;
        $prefix = __NAMESPACE__.'\\'.__FUNCTION__;
        $this->createClass("{$prefix}Class1", "
            namespace ".__NAMESPACE__.";
            class {$name}Class1 {
                /** @primary */ 
                public \${$name}1id;
                
                /** @field */ 
                public \${$name}2Id;
                
                /** @has.many.of {$name}Class2 */
                public \${$name}2;
            }
        ");
        $this->createClass("{$prefix}Class2", "
            namespace ".__NAMESPACE__.";
            class {$name}Class2 {
                /** @primary */ 
                public \${$name}2Id;
            }
        ");
        $meta = $mapper->getMeta("{$prefix}Class1");
        $expected = array(
            $name.'2'=>array('many', 'of'=>$name."Class2")
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
        $prefix = __NAMESPACE__."\\".__FUNCTION__;
        $name = __FUNCTION__;
        $this->createClass("{$prefix}Class1", "
            namespace ".__NAMESPACE__.";
            class {$name}Class1 {
                /** @has test */ 
                public \$test;
            }
        ");
        $meta = $mapper->getMeta("{$prefix}Class1");
        $expected = array(
            'test'=>array('test')
        );
        $this->assertEquals($expected, $meta->relations);
    }

    /**
     * @covers Amiss\Mapper\Note::buildRelations
     */
    public function testGetMetaRelationWithCompositeKeyAsStrings()
    {
        $mapper = new \Amiss\Mapper\Note;
        $name = __FUNCTION__;
        $prefix = __NAMESPACE__."\\".__FUNCTION__;
        $this->createClass("{$prefix}Foo", "
            namespace ".__NAMESPACE__.";
            class {$name}Foo {
                /** 
                 * @has.one.of stdClass
                 * @has.one.on barId
                 * @has.one.on bazId
                 */
                public \$bar;
            }
        ");
        $meta = $mapper->getMeta("{$prefix}Foo");
        $expected = array(
            'bar'=>array('one', 'of'=>'stdClass', 'on'=>array('barId', 'bazId')),
        );
        $this->assertEquals($expected, $meta->relations);
    }

    /**
     * @covers Amiss\Mapper\Note::buildRelations
     */
    public function testGetMetaRelationWithCompositeKeyAsHash()
    {
        $mapper = new \Amiss\Mapper\Note;
        $name = __FUNCTION__;
        $prefix = __NAMESPACE__."\\".__FUNCTION__;
        $this->createClass("{$prefix}Foo", "
            namespace ".__NAMESPACE__.";
            class {$name}Foo {
                /** 
                 * @has.one.of stdClass
                 * @has.one.on.barId otherBarId
                 * @has.one.on.bazId otherBazId
                 */
                public \$bar;
            }
        ");
        $meta = $mapper->getMeta("{$prefix}Foo");
        $expected = array(
            'bar'=>array('one', 'of'=>'stdClass', 'on'=>array('barId'=>'otherBarId', 'bazId'=>'otherBazId')),
        );
        $this->assertEquals($expected, $meta->relations);
    }
}
