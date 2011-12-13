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
	 */
	public function testDefaultObjectToTableMapping($name, $result)
	{
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
	 * @dataProvider dataForCustomDefaultObjectToTableMappingWithCallable
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
