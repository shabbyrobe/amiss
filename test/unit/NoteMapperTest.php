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
	 * @covers Amiss\Mapper\Note::createMeta
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
	 * @covers Amiss\Mapper\Note::createMeta
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
	 * @covers Amiss\Mapper\Note::createMeta
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
	 * @covers Amiss\Mapper\Note::createMeta
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
	 * @covers Amiss\Mapper\Note::createMeta
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
	 * @covers Amiss\Mapper\Note::createMeta
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
	 * @covers Amiss\Mapper\Note::createMeta
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
	 * @covers Amiss\Mapper\Note::createMeta
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
	 * @covers Amiss\Mapper\Note::createMeta
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
	 * @covers Amiss\Mapper\Note::createMeta
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
	 * @covers Amiss\Mapper\Note::createMeta
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
	 * @covers Amiss\Mapper\Note::createMeta
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
	 * @covers Amiss\Mapper\Note::createMeta
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
				 * @has one {$name}Bar barId
				 */
				public function getBar()
				{
					return \$this->bar;
				}
			}
		");
		$meta = $mapper->getMeta(__NAMESPACE__."\\{$name}Foo");
		$expected = array(
			'bar'=>array('one'=>$name."Bar", 'on'=>array('barId'=>'barId'), 'getter'=>'getBar', 'setter'=>'setBar'),
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
				 * @has one {$name}Bar barId
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
			'bar'=>array('one'=>$name."Bar", 'on'=>array('barId'=>'barId'), 'getter'=>'getBar', 'setter'=>'setLaDiDaBar'),
		);
		$this->assertEquals($expected, $meta->relations);
	}
	
	/**
	 * @group mapper
	 * @group unit
	 * @group faulty
	 * 
	 * @covers Amiss\Mapper\Note::createMeta
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
				
				/** @has one {$name}Class2 */
				public \${$name}2;
			}
			class {$name}Class2 {
				/** @primary */ 
				public \${$name}2Id;
			}
		");
		$meta = $mapper->getMeta(__NAMESPACE__."\\{$name}Class1");
		$expected = array(
			$name.'2'=>array('one'=>$name."Class2", 'on'=>null)
		);
		$this->assertEquals($expected, $meta->relations);
	}

	/**
	 * @group mapper
	 * @group unit
	 * 
	 * @covers Amiss\Mapper\Note::createMeta
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
				
				/** @has many {$name}Class2 */
				public \${$name}2;
			}
			class {$name}Class2 {
				/** @primary */ 
				public \${$name}2Id;
			}
		");
		$meta = $mapper->getMeta(__NAMESPACE__."\\{$name}Class1");
		$expected = array(
			$name.'2'=>array('many'=>$name."Class2", 'on'=>null)
		);
		$this->assertEquals($expected, $meta->relations);
	}

	/**
	 * The data provider for this test creates invalid relations, but these
	 * are not validated by the mapper. When they are, this will need to be
	 * unrolled to create the valid classes and relation annotations for each 
	 * type of test.
	 * 
	 * @group mapper
	 * @group unit
	 * 
	 * @covers Amiss\Mapper\Note::createMeta
	 * @covers Amiss\Mapper\Note::buildRelations
	 * 
	 * @dataProvider dataForGetMetaOneToOnePropertyRelationWithOn
	 */
	public function testGetMetaPropertyRelationWithOn($index, $relType, $onSpec, $result)
	{
		$mapper = new \Amiss\Mapper\Note;
		$mapper->objectNamespace = __NAMESPACE__."\\Test{$index}";
		$name = __FUNCTION__;
		eval("
			namespace {$mapper->objectNamespace};
			class {$name}Class1 {
				/** @primary */ 
				public \$class1id;
				
				/** @field */ 
				public \$class2Id;
				
				/** @has {$relType} {$name}Class2 {$onSpec} */
				public \$class2;
			}
			class {$name}Class2 {
				/** @primary */ 
				public \$class2Id;
			}
		");
		$meta = $mapper->getMeta($mapper->objectNamespace."\\{$name}Class1");
		$expected = array(
			'class2'=>array($relType=>$name."Class2", 'on'=>$result)
		);
		$this->assertEquals($expected, $meta->relations);
	}
	
	public function dataForGetMetaOneToOnePropertyRelationWithOn()
	{ 
		$tests = array(
			array('one', 'class2Id', array('class2Id'=>'class2Id')),
			
			// single, same
			array('one', 'class2Id=class2Id', array('class2Id'=>'class2Id')),
			
			// single, different
			array('one', 'class2IdLeft=class2IdRight', array('class2IdLeft'=>'class2IdRight')),
			
			// multi-column "on" with left and right
			array('one', 'class2IdLeft=class2IdRight&class1IdLeft=class1IdRight', array('class2IdLeft'=>'class2IdRight', 'class1IdLeft'=>'class1IdRight')),
			
			// multi-column "on" with mix-n-match
			array('one', 'class2IdLeft=class2IdRight&class1IdLeft', array('class2IdLeft'=>'class2IdRight', 'class1IdLeft'=>'class1IdLeft')),
			
			// multi-column "on" with mix-n-match and readability whitespace
			array('one', 'class2IdLeft = class2IdRight & class1IdLeft', array('class2IdLeft'=>'class2IdRight', 'class1IdLeft'=>'class1IdLeft')),
			
			// multi-column "on" with mix-n-match, readability whitespace and underscores
			array('one', 'class_2_Id_Left = class_2_Id_Right & class_1_Id_Left', array('class_2_Id_Left'=>'class_2_Id_Right', 'class_1_Id_Left'=>'class_1_Id_Left')),
		);
		
		foreach ($tests as $idx=>&$item) {
			array_unshift($item, $idx);
		}
		
		return $tests;
	}
}
