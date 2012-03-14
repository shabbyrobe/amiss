<?php

namespace Amiss\Test\Acceptance;

class NoteMapperTest extends \CustomTestCase
{
	public function setUp()
	{
	}

	/**
	 * @group mapper
	 * @covers Amiss\Mapper\Note::getMeta
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
	 * @covers Amiss\Mapper\Note::getMeta
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
	 * @covers Amiss\Mapper\Note::getMeta
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
	 * @covers Amiss\Mapper\Note::getMeta
	 * @expectedException UnexpectedValueException
	 */
	public function testGetMetaThrowsWhenMultiplePrimariesSet()
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
	}
	
	/**
	 * @group mapper
	 * @covers Amiss\Mapper\Note::getMeta
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
	 * @covers Amiss\Mapper\Note::getMeta
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
	 * @covers Amiss\Mapper\Note::getMeta
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
	 * @covers Amiss\Mapper\Note::getMeta
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
	 * @covers Amiss\Mapper\Note::getMeta
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
		$this->assertEquals('id', $meta->primary);
	}
	
	/**
	 * @group mapper
	 * @covers Amiss\Mapper\Note::getMeta
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
	 * @covers Amiss\Mapper\Note::getMeta
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
	 * @group unimplemented
	 * @covers Amiss\Mapper\Note::getMeta
	 */
	public function testGetMetaOneToOnePropertyRelationWithNoOn()
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
				
				/** @has one {$name}Class2 */
				public \${$name}2;
			}
			class {$name}Class2 {
				/** @primary */ 
				public \${$name}2Id;
			}
		");
		$meta = $mapper->getMeta(__NAMESPACE__."\\{$name}Class1");
		$relations = $meta->getRelations();
		$expected = array(
			$name.'2'=>array('one'=>$name."Class2", 'on'=>null)
		);
		$this->assertEquals($expected, $relations);
	}

	/**
	 * @group mapper
	 * @group unimplemented
	 * @covers Amiss\Mapper\Note::getMeta
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
		$relations = $meta->getRelations();
		$expected = array(
			$name.'2'=>array('many'=>$name."Class2", 'on'=>null)
		);
		$this->assertEquals($expected, $relations);
	}

	/**
	 * The data provider for this test creates invalid relations, but these
	 * are not validated by the mapper. When they are, this will need to be
	 * unrolled to create the valid classes and relation annotations for each 
	 * type of test.
	 * 
	 * @group mapper
	 * @group unimplemented
	 * @covers Amiss\Mapper\Note::getMeta
	 * @covers Amiss\Mapper\Note::buildRelations
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
		$relations = $meta->getRelations();
		$expected = array(
			'class2'=>array($relType=>$name."Class2", 'on'=>$result)
		);
		$this->assertEquals($expected, $relations);
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
		);
		
		foreach ($tests as $idx=>&$item) {
			array_unshift($item, $idx);
		}
		
		return $tests;
	}
}
