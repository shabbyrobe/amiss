<?php

namespace Amiss\Test\Acceptance;

class NoteMapperTest extends \CustomTestCase
{
	public function setUp()
	{
	}

	/**
	 * @group mapper
	 * @group unit
	 * @covers Amiss\Mapper\Note::loadMeta
	 */
	public function testGetMetaWithDefinedTable()
	{
		$mapper = new \Amiss\Mapper\Note;
		eval("
			namespace ".__NAMESPACE__.";
			/** @table custom_table */
			class ".__FUNCTION__." {}
		");
		$meta = $mapper->getMeta(__NAMESPACE__.'\\'.__FUNCTION__);
		$this->assertEquals('custom_table', $meta->table);
	}

	/**
	 * @group mapper
	 * @group unit
	 * @covers Amiss\Mapper\Note::loadMeta
	 */
	public function testGetMetaWithDefaultTable()
	{
		$mapper = $this->getMockBuilder('\Amiss\Mapper\Note')
			->setMethods(array('getDefaultTable'))
			->getMock()
		;
		$mapper->expects($this->once())->method('getDefaultTable');
		
		eval("
			namespace ".__NAMESPACE__.";
			class ".__FUNCTION__." {}
		");
		
		$meta = $mapper->getMeta(__NAMESPACE__.'\\'.__FUNCTION__);
	}
	
	/**
	 * @group mapper
	 * @group unit
	 * @covers Amiss\Mapper\Note::loadMeta
	 */
	public function testGetMetaCache()
	{
		$cache = array();
		$getCount = $setCount = 0;
		
		$mapper = new \Amiss\Mapper\Note(array(
			function($key) use (&$cache, &$getCount) {
				++$getCount;
				return isset($cache[$key]) ? $cache[$key] : null;
			},
			function($key, $value) use (&$cache, &$setCount) {
				++$setCount;
				$cache[$key] = $value;
			},
		));
		
		$this->assertArrayNotHasKey('stdClass', $cache);
		$meta = $mapper->getMeta('stdClass');
		$this->assertArrayHasKey('stdClass', $cache);
		$this->assertEquals(1, $getCount);
		$this->assertEquals(1, $setCount);
		
		$meta = $mapper->getMeta('stdClass');
		$this->assertEquals(2, $getCount);
		$this->assertEquals(1, $setCount);
	}
	
	/**
	 * @group mapper
	 * @group unit
	 * @covers Amiss\Mapper\Note::loadMeta
	 */
	public function testGetMetaMultiplePrimaries()
	{
		$mapper = new \Amiss\Mapper\Note;
		eval('
			namespace '.__NAMESPACE__.';
			class '.__FUNCTION__.' {
				/** @primary */ public $id1;
				/** @primary */ public $id2;
			}
		');
		$meta = $mapper->getMeta(__NAMESPACE__.'\\'.__FUNCTION__);
		$this->assertEquals(array('id1', 'id2'), $meta->primary);
	}
	
	/**
	 * @group mapper
	 * @group unit
	 * @covers Amiss\Mapper\Note::loadMeta
	 */
	public function testGetMetaFieldsFound()
	{
		$mapper = new \Amiss\Mapper\Note;
		eval('
			namespace '.__NAMESPACE__.';
			class '.__FUNCTION__.' {
				/** @field */ public $foo;
				/** @field */ public $bar;
			}
		');
		
		$meta = $mapper->getMeta(__NAMESPACE__.'\\'.__FUNCTION__);
		$this->assertEquals(array('foo', 'bar'), array_keys($meta->getFields()));
	}
	
	/**
	 * @group mapper
	 * @group unit
	 * @covers Amiss\Mapper\Note::loadMeta
	 */
	public function testGetMetaSkipsPropertiesWithNoFieldNote()
	{
		$mapper = new \Amiss\Mapper\Note;
		eval('
			namespace '.__NAMESPACE__.';
			class '.__FUNCTION__.' {
				public $notAField;
				
				/** @field */ public $yepAField;
			}
		');
		$meta = $mapper->getMeta(__NAMESPACE__.'\\'.__FUNCTION__);
		$this->assertEquals(array('yepAField'), array_keys($meta->getFields()));
	}
	
	/**
	 * @group mapper
	 * @group unit
	 * @covers Amiss\Mapper\Note::loadMeta
	 */
	public function testGetMetaGetterWithDefaultSetter()
	{
		$mapper = new \Amiss\Mapper\Note;
		eval('
			namespace '.__NAMESPACE__.';
			class '.__FUNCTION__.' {
				/** @field */
				public function getFoo(){}
				public function setFoo($value){} 
			}
		');
		$meta = $mapper->getMeta(__NAMESPACE__.'\\'.__FUNCTION__);
		$expected = array('name'=>'foo', 'type'=>null, 'getter'=>'getFoo', 'setter'=>'setFoo');
		$this->assertEquals($expected, $meta->getField('foo'));
	}

	/**
	 * @group mapper
	 * @group unit
	 * @covers Amiss\Mapper\Note::loadMeta
	 */
	public function testGetMetaPrimaryNoteImpliesFieldNote()
	{
		$mapper = new \Amiss\Mapper\Note;
		eval('
			namespace '.__NAMESPACE__.';
			class '.__FUNCTION__.' {
				/** @primary */ public $id;
			}
		');
		$meta = $mapper->getMeta(__NAMESPACE__.'\\'.__FUNCTION__);
		$this->assertEquals(array('id'), array_keys($meta->getFields()));
	}

	/**
	 * @group mapper
	 * @group unit
	 * @covers Amiss\Mapper\Note::loadMeta
	 */
	public function testGetMetaPrimaryNoteImpliedFieldNoteAllowsTypeSet()
	{
		$mapper = new \Amiss\Mapper\Note;
		eval('
			namespace '.__NAMESPACE__.';
			class '.__FUNCTION__.' {
				/**
				 * @primary
				 * @type autoinc 
				 */ 
				public $id;
			}
		');
		$meta = $mapper->getMeta(__NAMESPACE__.'\\'.__FUNCTION__);
		$this->assertEquals(array('id'=>array('name'=>'id', 'type'=>'autoinc')), $meta->getFields());
	}
	
	/**
	 * @group mapper
	 * @group unit
	 * @covers Amiss\Mapper\Note::loadMeta
	 */
	public function testGetMetaPrimaryNoteFound()
	{
		$mapper = new \Amiss\Mapper\Note;
		eval('
			namespace '.__NAMESPACE__.';
			class '.__FUNCTION__.' {
				/** @primary */ public $id;
			}
		');
		$meta = $mapper->getMeta(__NAMESPACE__.'\\'.__FUNCTION__);
		$this->assertEquals(array('id'), $meta->primary);
	}

	/**
	 * @group mapper
	 * @group unit
	 * @covers Amiss\Mapper\Note::loadMeta
	 */
	public function testGetMetaMultiPrimaryNoteFound()
	{
		$mapper = new \Amiss\Mapper\Note;
		eval('
			namespace '.__NAMESPACE__.';
			class '.__FUNCTION__.' {
				/** @primary */ public $idPart1;
				/** @primary */ public $idPart2;
			}
		');
		$meta = $mapper->getMeta(__NAMESPACE__.'\\'.__FUNCTION__);
		$this->assertEquals(array('idPart1', 'idPart2'), $meta->primary);
	}
	
	/**
	 * @group mapper
	 * @group unit
	 * @covers Amiss\Mapper\Note::loadMeta
	 */
	public function testGetMetaFieldTypeFound()
	{
		$mapper = new \Amiss\Mapper\Note;
		eval('
			namespace '.__NAMESPACE__.';
			class '.__FUNCTION__.' {
				/** 
				 * @field
				 * @type foobar
				 */
				 public $id;
			}
		');
		$meta = $mapper->getMeta(__NAMESPACE__.'\\'.__FUNCTION__);
		$field = $meta->getField('id');
		$this->assertEquals('foobar', $field['type']);
	}

	/**
	 * @group mapper
	 * @group unit
	 * @covers Amiss\Mapper\Note::loadMeta
	 */
	public function testGetMetaWithParentClass()
	{
		$mapper = new \Amiss\Mapper\Note;
		eval('
			namespace '.__NAMESPACE__.';
			class '.__FUNCTION__.'1 {
				/** @field */ public $foo;
			}
			class '.__FUNCTION__.'2 extends '.__FUNCTION__.'1 {
				/** @field */ public $bar;
			}
		');
		
		$meta1 = $mapper->getMeta(__NAMESPACE__.'\\'.__FUNCTION__.'1');
		$meta2 = $mapper->getMeta(__NAMESPACE__.'\\'.__FUNCTION__.'2');
		$this->assertEquals($meta1, $this->getProtected($meta2, 'parent'));
	}

	/**
	 * @group mapper
	 * @group unit
	 * @covers Amiss\Mapper\Note::buildRelations
	 * @covers Amiss\Mapper\Note::findGetterSetter
	 */
	public function testGetMetaRelationWithInferredGetterAndInferredSetter()
	{
		$mapper = new \Amiss\Mapper\Note;
		$name = __FUNCTION__;
		eval("
			namespace ".__NAMESPACE__.";
			class {$name}Foo {
				/** @primary */ 
				public \$id;
				/** @field */
				public \$barId;
				
				private \$bar;
				
				/** 
				 * @has one of={$name}Bar; on=barId
				 */
				public function getBar()
				{
					return \$this->bar;
				}
			}
		");
		$meta = $mapper->getMeta(__NAMESPACE__."\\{$name}Foo");
		$expected = array(
			'bar'=>array('one', 'of'=>$name."Bar", 'on'=>'barId', 'getter'=>'getBar', 'setter'=>'setBar'),
		);
		$this->assertEquals($expected, $meta->relations);
	}

	/**
	 * @group mapper
	 * @group unit
	 * @covers Amiss\Mapper\Note::buildRelations
	 * @covers Amiss\Mapper\Note::findGetterSetter
	 */
	public function testGetMetaRelationWithInferredGetterAndExplicitSetter()
	{
		$mapper = new \Amiss\Mapper\Note;
		$name = __FUNCTION__;
		eval("
			namespace ".__NAMESPACE__.";
			class {$name}Foo {
				/** @primary */ 
				public \$id;
				/** @field */
				public \$barId;
				
				private \$bar;
				
				/** 
				 * @has one of={$name}Bar; on=barId
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
		$meta = $mapper->getMeta(__NAMESPACE__."\\{$name}Foo");
		$expected = array(
			'bar'=>array('one', 'of'=>$name."Bar", 'on'=>'barId', 'getter'=>'getBar', 'setter'=>'setLaDiDaBar'),
		);
		$this->assertEquals($expected, $meta->relations);
	}
	
	/**
	 * @group mapper
	 * @group unit
	 * @group faulty
	 * 
	 * @covers Amiss\Mapper\Note::loadMeta
	 */
	public function testGetMetaOneToOnePropertyRelationWithNoOn()
	{
		throw new \Exception('not implemented');
		
		$mapper = new \Amiss\Mapper\Note;
		$name = __FUNCTION__;
		eval("
			namespace ".__NAMESPACE__.";
			class {$name}Class1 {
				/** @primary */ 
				public \${$name}1id;
				
				/** @field */ 
				public \${$name}2Id;
				
				/** @has one of={$name}Class2 */
				public \${$name}2;
			}
			class {$name}Class2 {
				/** @primary */ 
				public \${$name}2Id;
			}
		");
		$meta = $mapper->getMeta(__NAMESPACE__."\\{$name}Class1");
		$expected = array(
			$name.'2'=>array('one', 'of'=>$name."Class2", 'on'=>null)
		);
		$this->assertEquals($expected, $meta->relations);
	}

	/**
	 * @group mapper
	 * @group unit
	 * 
	 * @covers Amiss\Mapper\Note::loadMeta
	 * @covers Amiss\Mapper\Note::buildRelations
	 */
	public function testGetMetaOneToManyPropertyRelationWithNoOn()
	{
		$mapper = new \Amiss\Mapper\Note;
		$name = __FUNCTION__;
		eval("
			namespace ".__NAMESPACE__.";
			class {$name}Class1 {
				/** @primary */ 
				public \${$name}1id;
				
				/** @field */ 
				public \${$name}2Id;
				
				/** @has many of={$name}Class2 */
				public \${$name}2;
			}
			class {$name}Class2 {
				/** @primary */ 
				public \${$name}2Id;
			}
		");
		$meta = $mapper->getMeta(__NAMESPACE__."\\{$name}Class1");
		$expected = array(
			$name.'2'=>array('many', 'of'=>$name."Class2")
		);
		$this->assertEquals($expected, $meta->relations);
	}
	
	/**
	 * @group unit
	 * @covers Amiss\Mapper\Note::buildRelations
	 * @dataProvider dataForBuildRelations
	 */
	public function testBuildRelations($note, $expected)
	{
		$mapper = new \Amiss\Mapper\Note;
		$notes = array('myprop'=>array(
			'has'=>$note
		));
		$result = $this->callProtected($mapper, 'buildRelations', $notes);
		$this->assertEquals($expected, $result['myprop']);
	}
	
	public function dataForBuildRelations()
	{
		return array(
			array('one of=Foobar', array('one', 'of'=>'Foobar')),
			
			// straight query string
			array('one of=Foobar&on[foo]=bar', array('one', 'of'=>'Foobar', 'on'=>array('foo'=>'bar'))),
			
			// some whitespace around the equals
			array('one of = Foobar&on[foo] = bar', array('one', 'of'=>'Foobar', 'on'=>array('foo'=>'bar'))),
			
			// straight multi-column on
			array('one of=Foobar&on[foo]=bar&on[baz]=qux', array('one', 'of'=>'Foobar', 'on'=>array('foo'=>'bar', 'baz'=>'qux'))),
			
			// multi-column on with whitespace 
			array('one of = Foobar & on[foo] = bar & on[baz] = qux', array('one', 'of'=>'Foobar', 'on'=>array('foo'=>'bar', 'baz'=>'qux'))),
			
			// multi-column on with no whitespace and semicolon 
			array('one of=Foobar;on[foo]=bar;on[baz]=qux', array('one', 'of'=>'Foobar', 'on'=>array('foo'=>'bar', 'baz'=>'qux'))),
			
			// multi-column on with a little bit of whitespace and semicolon 
			array('one of=Foobar; on[foo]=bar; on[baz]=qux', array('one', 'of'=>'Foobar', 'on'=>array('foo'=>'bar', 'baz'=>'qux'))),
			
			// multi-column on with heaps of whitespace, semicolon and ampersand mix-n-match
			array('   one of=Foobar    ;  on[foo] =    bar  &     on[baz]    =   qux   ', array('one', 'of'=>'Foobar', 'on'=>array('foo'=>'bar', 'baz'=>'qux'))),
			
			// crazy characters in values
			array('one of=Foo\Bar; on[foo]=`bar`', array('one', 'of'=>'Foo\Bar', 'on'=>array('foo'=>'`bar`'))),
			
			// URL encoding
			array('one of=%20%26%3B%3D%20', array('one', 'of'=>' &;= ')),
		);
	}
}
