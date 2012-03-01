<?php

namespace Amiss\Test\Unit;

use Amiss\Manager;

class ManagerNameMappingTest extends \CustomTestCase
{
	public function setUp()
	{ 
		$this->manager = new Manager(array());
	}
	
	public function testDefaultPropertyToColumn()
	{
		$result = array(
			'fooBar'=>'yep',
			'fooBaz'=>'wee',
			'pants_yep'=>'yepp',
		);
		$class = (object)$result;
		$values = $this->manager->getDefaultRowValues($class);
		$this->assertEquals($result, $values);
	}
	
	public function testCustomDefaultPropertyToColumn()
	{
		$this->manager->propertyColumnMapper = new TestPropertyMapper; 
		
		$class = (object)array(
			'fooBar'=>'yep',
			'fooBaz'=>'wee',
			'pants_yep'=>'yepp',
		);
		$values = $this->manager->getDefaultRowValues($class);
		$this->assertEquals(array('field_fooBar'=>'yep', 'field_fooBaz'=>'wee', 'field_pants_yep'=>'yepp'), $values);
	}
	
	public function testDefaultColumnToProperty()
	{
		$stmt = $this->getMock('stdClass', array('fetch'));
		$stmt->expects($this->any())->method('fetch')->will($this->returnValue(array('foo'=>'bar', 'fizz'=>'buzz')));
		$obj = $this->manager->fetchObject($stmt, 'stdClass');
		$this->assertEquals((object)array(
				'foo'=>'bar',
				'fizz'=>'buzz',	
			), 
			$obj
		); 
	}
	
	public function testDefaultObjectToRowConvertsUnderscoresWhenSet()
	{
		$this->manager->convertFieldUnderscores = true;
		$class = (object)array(
			'fooBar'=>'yep',
			'fooBaz'=>'yeppo'
		);
		$values = $this->callProtected($this->manager, 'exportRow', $class);
		$this->assertEquals(array('foo_bar'=>'yep', 'foo_baz'=>'yeppo'), $values);
	}
	
	public function testDefaultObjectToRowDoesntConvertsUnderscoresWhenDisabled()
	{
		$this->manager->convertFieldUnderscores = false;
		$class = (object)array(
			'fooBar'=>'yep',
			'fooBaz'=>'yeppo'
		);
		$values = $this->callProtected($this->manager, 'exportRow', $class);
		$this->assertEquals(array('fooBar'=>'yep', 'fooBaz'=>'yeppo'), $values);
	}

	public function testDefaultRowToObjectConvertsUnderscoresWhenSet()
	{
		$manager = $this->getMock('Amiss\Manager', array('resolveObjectName'), array(array()));
		$manager->convertFieldUnderscores = true;
		$manager->expects($this->any())->method('resolveObjectName')->will($this->returnValue('\stdClass'));
		$row = array(
			'foo_bar'=>'yep',
			'foo_baz'=>'yeppo'
		);
		$stmt = $this->getMock('stdClass', array('fetch'));
		$stmt->expects($this->any())->method('fetch')->will($this->returnValue($row));
		$object = $manager->fetchObject($stmt, '');
		
		$expected = (object)array(
			'fooBar'=>'yep',
			'fooBaz'=>'yeppo',
		);
		$this->assertEquals($expected, $object);
	}

	public function testDefaultRowToObjectDoesntConvertsUnderscoresWhenDisabled()
	{
		$manager = $this->getMock('Amiss\Manager', array('resolveObjectName'), array(array()));
		$manager->convertFieldUnderscores = false;
		$manager->expects($this->any())->method('resolveObjectName')->will($this->returnValue('\stdClass'));
		$row = array(
			'foo_bar'=>'yep',
			'foo_baz'=>'yeppo'
		);
		$stmt = $this->getMock('stdClass', array('fetch'));
		$stmt->expects($this->any())->method('fetch')->will($this->returnValue($row));
		$object = $manager->fetchObject($stmt, '');
		
		$expected = (object)array(
			'foo_bar'=>'yep',
			'foo_baz'=>'yeppo',
		);
		$this->assertEquals($expected, $object);
	}
	
	public function testCustomDefaultColumnToProperty()
	{
		$this->manager->propertyColumnMapper = new TestPropertyMapper;
		
		$row = array('field_foo'=>'bar', 'field_fizz'=>'buzz');
		$result =(object)array(
			'foo'=>'bar',
			'fizz'=>'buzz',	
		);
		
		$stmt = $this->getMock('stdClass', array('fetch'));
		$stmt->expects($this->any())->method('fetch')->will($this->returnValue($row));
		
		$obj = $this->manager->fetchObject($stmt, 'stdClass');
		$this->assertEquals($result, $obj); 
	}
	
	/**
	 * @dataProvider dataForDefaultObjectToTableMapping
	 * @covers Amiss\Manager::getTableName
	 */
	public function testDefaultObjectToTableMappingWhenConvertEnabled($name, $result)
	{
		$this->manager->convertTableNames = true;
		$table = $this->manager->getTableName($name);
		$this->assertEquals($result, $table);
	}
	
	public function dataForDefaultObjectToTableMapping()
	{
		return array(
			array('Artist', '`artist`'),
			array('ArtistPants', '`artist_pants`'),
			array('ArtistPantsBurger', '`artist_pants_burger`'),
		);
	}
	
	/**
	 * @covers Amiss\Manager::getTableName
	 */
	public function testDefaultObjectToTableMappingWhenConvertDisabled()
	{
		$this->manager->convertTableNames = false;
		$table = $this->manager->getTableName('FooBar');
		$this->assertEquals('`FooBar`', $table);
	}
	
	/**
	 * @dataProvider dataForCustomDefaultObjectToTableMappingWithCallable
	 * @covers Amiss\Manager::getTableName
	 */
	public function testCustomDefaultObjectToTableMappingWithCallable($name, $result)
	{
		$this->manager->objectToTableMapper = function ($name) {
			return strtoupper($name);
		};
		$table = $this->manager->getTableName($name);
		$this->assertEquals($result, $table);
	}
	
	public function dataForCustomDefaultObjectToTableMappingWithCallable()
	{
		return array(
			array('Artist', '`ARTIST`'),
			array('ArtistPants', '`ARTISTPANTS`'),
		);
	}
	
	public function testCustomDefaultObjectToTableMappingWithNameMapper()
	{
		$this->manager->objectToTableMapper = new TestPropertyMapper;
		$table = $this->manager->getTableName('ArtistPants');
		$this->assertEquals('`field_ArtistPants`', $table);
	}
	
	public function testExplicitObjectToTableMappingWithoutDefaultObjectNamespace()
	{
		$this->manager->tableMap = array(
			'Artist'=>'burger',
		);
		
		$table = $this->manager->getTableName('Artist');
		$this->assertEquals('`burger`', $table);
	}
}

class TestPropertyMapper implements \Amiss\Name\Mapper
{
	public function to(array $names)
	{
		$trans = array();
		foreach ($names as $n) {
			$trans[$n] = 'field_'.$n;
		}
		return $trans;
	}
	
	public function from(array $names)
	{
		$trans = array();
		foreach ($names as $n) {
			$trans[$n] = substr($n, 6);
		}
		return $trans;
	}
}
