<?php

namespace Amiss\Test\Unit\Active;

class RecordTest extends \CustomTestCase
{
	public function setUp()
	{
		\Amiss\Active\Record::_reset();
		$this->db = new \PDO('sqlite::memory:', null, null, array(\PDO::ATTR_ERRMODE=>\PDO::ERRMODE_EXCEPTION));
		$this->manager = new \Amiss\Manager($this->db);
		$this->manager->objectNamespace = 'Amiss\Test\Unit';
	}
	
	/**
	 * @covers Amiss\Active\Record::createMeta
	 */
	public function testCreateMeta()
	{
		$class = __NAMESPACE__.'\TestActiveRecord1';
		$meta = $this->callProtected('Amiss\Active\Record', 'createMeta', $class);
		$this->assertInstanceOf('Amiss\Active\Meta', $meta);
		$this->assertInstanceOf('Amiss\Active\Meta', $meta->parent);
		$this->assertNull($meta->parent->parent);
		$this->assertEquals($meta->class, $class);
	}
	
	/**
	 * @covers Amiss\Active\Record::getMeta
	 */
	public function testGetMeta()
	{
		$meta = TestActiveRecord1::getMeta();
		$this->assertInstanceOf('Amiss\Active\Meta', $meta);
		$this->assertEquals(__NAMESPACE__.'\TestActiveRecord1', $meta->class);
		
		// ensure the instsance is cached
		$this->assertTrue($meta === TestActiveRecord1::getMeta());
	}
	
	/**
	 * @covers Amiss\Active\Meta::getManager
	 */
	public function testRegisterTables()
	{
		\Amiss\Active\Record::setManager($this->manager);
		$c1 = TestActiveRecord1::getConnector();
		$c2 = TestActiveRecord2::getConnector();
		$this->assertTrue($c1 === $c2);
		$this->assertEquals(
			array(
				__NAMESPACE__.'\TestActiveRecord1'=>'table_1',
				__NAMESPACE__.'\TestActiveRecord2'=>'table_2'
			), 
			$this->manager->tableMap
		);
	}
	
	public function testMultiConnection()
	{
		\Amiss\Active\Record::setManager($this->manager);
		$manager2 = clone $this->manager;
		$this->assertFalse($this->manager === $manager2);
		OtherConnBase::setManager($manager2);
		
		$c1 = TestOtherConnRecord1::getManager();
		$c2 = TestOtherConnRecord2::getManager();
		$this->assertTrue($c1 === $c2);
		
		$c3 = TestActiveRecord1::getManager();
		$this->assertFalse($c1 === $c3); 
	}
	
	/**
	 * @covers Amiss\Active\Record::__callStatic
	 */
	public function testGetForwarded()
	{
		$manager = $this->getMock('Amiss\Manager', array('get'), array($this->db));
		$manager->objectNamespace = __NAMESPACE__;
		$manager->expects($this->once())->method('get')->with(
			$this->equalTo(__NAMESPACE__.'\TestActiveRecord1'), 
			$this->equalTo('pants=?'), 
			$this->equalTo(1)
		);
		\Amiss\Active\Record::setManager($manager);
		$tar = new TestActiveRecord1;
		TestActiveRecord1::get('pants=?', 1);
	}
	
	/**
	 * @covers Amiss\Active\Record::__callStatic
	 */
	public function testGetByExplicitPk()
	{
		$manager = $this->getMock('Amiss\Manager', array('get'), array($this->db));
		$manager->objectNamespace = __NAMESPACE__;
		\Amiss\Active\Record::setManager($manager);
		
		$manager->expects($this->once())->method('get')->with(
			$this->equalTo(__NAMESPACE__.'\TestActiveRecord1'), 
			$this->equalTo('fooBar=?'), 
			$this->equalTo(1)
		);
		TestActiveRecord1::getByPk(1);
	}
	
	/**
	 * @covers Amiss\Active\Record::__callStatic
	 * @covers Amiss\Active\Record::getByPk
	 */
	public function testGetByImplicitPk()
	{
		$manager = $this->getMock('Amiss\Manager', array('get'), array($this->db));
		$manager->objectNamespace = __NAMESPACE__;
		\Amiss\Active\Record::setManager($manager);
		
		$manager->expects($this->once())->method('get')->with(
			$this->equalTo(__NAMESPACE__.'\TestActiveRecord2'), 
			$this->equalTo('testActiveRecord2Id=?'), 
			$this->equalTo(1)
		);
		TestActiveRecord2::getByPk(1);
	}
	
	/**
	 * @covers Amiss\Active\Record::__callStatic
	 * @covers Amiss\Active\Record::getByPk
	 */
	public function testManyImplicitPksWorkAsExpected()
	{
		$manager = $this->getMock('Amiss\Manager', array('get'), array($this->db));
		$manager->objectNamespace = __NAMESPACE__;
		\Amiss\Active\Record::setManager($manager);
		
		TestActiveRecord2::getByPk(1);
		TestActiveRecord3::getByPk(1);
		
		$this->assertEquals(TestActiveRecord2::getMeta()->primary, 'testActiveRecord2Id');
		$this->assertEquals(TestActiveRecord3::getMeta()->primary, 'testActiveRecord3Id');
	}
	
	/**
	 * @covers Amiss\Active\Record::fetchRelated
	 */
	public function testFetchRelatedSingle()
	{
		$manager = $this->getMock('Amiss\Manager', array('getRelated', 'getRelatedList'), array($this->db));
		$manager->objectNamespace = __NAMESPACE__;
		\Amiss\Active\Record::setManager($manager);
		
		$manager->expects($this->once())->method('getRelated')->with(
			$this->isInstanceOf(__NAMESPACE__.'\TestRelatedChild'),
			$this->equalTo(__NAMESPACE__.'\TestRelatedParent'), 
			$this->equalTo('parentId')
		)->will($this->returnValue(999));
		$manager->expects($this->never())->method('getRelatedList');
		
		$child = new TestRelatedChild;
		$child->childId = 6;
		$child->parentId = 1;
		$result = $child->fetchRelated('parent');
		$this->assertEquals(999, $result);
	}
	
	/**
	 * @covers Amiss\Active\Record::fetchRelated
	 */
	public function testFetchRelatedList()
	{
		$manager = $this->getMock('Amiss\Manager', array('getRelated', 'getRelatedList'), array($this->db));
		$manager->objectNamespace = __NAMESPACE__;
		\Amiss\Active\Record::setManager($manager);
		
		$manager->expects($this->once())->method('getRelatedList')->with(
			$this->isInstanceOf(__NAMESPACE__.'\TestRelatedParent'),
			$this->equalTo(__NAMESPACE__.'\TestRelatedChild'), 
			$this->equalTo('parentId')
		)->will($this->returnValue(123));
		$manager->expects($this->never())->method('getRelated');
		
		$parent = new TestRelatedParent;
		$parent->parentId = 2;
		$result = $parent->fetchRelated('children');
		$this->assertEquals(123, $result);
	}
	
	/**
	 * @covers Amiss\Active\Record::exportRow
	 */
	public function testExportRowRevertsToDefaultWhenFieldsNotDefined()
	{
		\Amiss\Active\Record::setManager($this->manager);
		$trc = new TestDefaultExportRowRecord();
		$values = $trc->exportRow();
		$this->assertEquals(
			array(
				'id'=>1,
				'int'=>1,
				'float'=>1.1,
				'string'=>'hello',
			),
			$values
		);
	}
	
	/**
	 * @covers Amiss\Active\Record::exportRow
	 */
	public function testExportRowUsesFieldsArrayWhenDefined()
	{
		\Amiss\Active\Record::setManager($this->manager);
		$r = new TestFieldExportRowRecord();
		$values = $r->exportRow();
		$this->assertEquals(
			array(
				'yep1'=>'a1',
				'yep2'=>'a2',
				'yep3'=>'a3',
				'yep4'=>'a4',
			),
			$values
		);
	}
	
	/**
	 * If a record has not been loaded from the database and the class doesn't
	 * define fields, undefined properties should return null. 
	 * 
	 * @covers Amiss\Active\Record::__get
	 */
	public function testGetUnknownPropertyWhenFieldsUndefinedOnNewObjectReturnsNull()
	{
		$ar = new TestActiveRecord1();
		$this->assertNull($ar->thisPropertyShouldNeverExist);
	}
	
	/**
	 * If the class defines its fields, undefined properties should always throw. 
	 * 
	 * @covers Amiss\Active\Record::__get
	 * @expectedException BadMethodCallException
	 */
	public function testGetUnknownPropertyWhenFieldsDefinedThrowsException()
	{
		$ar = new TestFieldExportRowRecord();
		$value = $ar->thisPropertyShouldNeverExist;
	}
	
	/**
	 * Even if the class doesn't define its fields, undefined properties should throw
	 * if the record has been loaded from the database as we can expect it is fully
	 * populated.
	 * 
	 * @expectedException BadMethodCallException
	 */
	public function testGetUnknownPropertyWhenFieldsUndefinedAfterRetrievingFromDatabaseThrowsException()
	{
		TestActiveRecord1::setManager($this->manager);
		$this->db->query("CREATE TABLE table_1(fooBar STRING);");
		$this->db->query("INSERT INTO table_1(fooBar) VALUES(123)");
		
		$ar = TestActiveRecord1::get('fooBar=123');
		$value = $ar->thisPropertyShouldNeverExist;
	}
	
	/**
	 * @covers Amiss\Active\Record::addTypeHandler
	 */
	public function testAddTypeHandler()
	{
		$handler = new \TestTypeHandler();
		
		$this->assertNull(\Amiss\Active\Record::getMeta()->getTypeHandler('foo'));
		
		\Amiss\Active\Record::addTypeHandler(new \TestTypeHandler(), 'foo');
		$handler2 = \Amiss\Active\Record::getMeta()->getTypeHandler('foo');
		
		$this->assertEquals($handler, $handler2);
	}
	
	/**
	 * @covers Amiss\Active\Record::addTypeHandler
	 */
	public function testAddTypeHandlerToManyTypes()
	{
		$handler = new \TestTypeHandler();
		
		$this->assertNull(\Amiss\Active\Record::getMeta()->getTypeHandler('foo'));
		$this->assertNull(\Amiss\Active\Record::getMeta()->getTypeHandler('bar'));
		
		\Amiss\Active\Record::addTypeHandler(new \TestTypeHandler(), array('foo', 'bar'));
		
		$this->assertEquals($handler, \Amiss\Active\Record::getMeta()->getTypeHandler('foo'));
		$this->assertEquals($handler, \Amiss\Active\Record::getMeta()->getTypeHandler('bar'));
	}
}

class TestActiveRecord1 extends \Amiss\Active\Record
{
	public static $table = 'table_1';
	public static $primary = 'fooBar';
	
	public $fooBar;
}

class TestActiveRecord2 extends \Amiss\Active\Record
{
	public static $table = 'table_2';
}

class TestActiveRecord3 extends \Amiss\Active\Record {}

abstract class OtherConnBase extends \Amiss\Active\Record {}

class TestOtherConnRecord1 extends OtherConnBase {}

class TestOtherConnRecord2 extends OtherConnBase {}

class TestRelatedParent extends \Amiss\Active\Record
{
	public $parentId;
	public static $relations = array(
		'children'=>array('many'=>'TestRelatedChild', 'on'=>'parentId')
	);
}

class TestRelatedChild extends \Amiss\Active\Record
{
	public $childId;
	public $parentId;
	public static $relations = array(
		'parent'=>array('one'=>'TestRelatedParent', 'on'=>'parentId')
	);
}

class TestDefaultExportRowRecord extends \Amiss\Active\Record
{
	public $id;
	public $int;
	public $float;
	public $string;
	public $array;
	public $null;
	public $resource;
	public $object;
	
	public function __construct()
	{
		$this->id = 1;
		$this->int = 1;
		$this->float = 1.1;
		$this->string = 'hello';
		$this->null = null;
		$this->resource = fopen('php://input', 'r');
		$this->object = new \stdClass;
	}
}

class TestFieldExportRowRecord extends \Amiss\Active\Record
{
	public static $fields = array(
		'yep1'=>true,
		// ensures that the 'value only' method works 
		'yep2',
	
		// ensures exporting isn't affected by future enhancements 
		'yep3'=>array(),
		'yep4'=>'a',
	);
	
	public $testFieldExportRowRecordId;
	public $yep1 = 'a1';
	public $yep2 = 'a2';
	public $yep3 = 'a3';
	public $yep4 = 'a4';
	public $nup1 = 'n1';
	public $nup2 = 'n2';
}
