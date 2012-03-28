<?php

namespace Amiss\Test\Unit;

use Amiss\Criteria;

class SelectQueryTest extends \CustomTestCase
{
	/**
	 * @group unit
	 * @covers Amiss\Criteria\Select::buildFields
	 */
	public function testBuildFieldsFromArrayWithoutMeta()
	{
		$criteria = new Criteria\Select;
		$criteria->fields = array('abc_def', 'ghi_jkl');
		
		$meta = null;
		$fields = $criteria->buildFields($meta);
		$this->assertEquals('`abc_def`, `ghi_jkl`', $fields);
	}
	
	/**
	 * @group unit
	 * @covers Amiss\Criteria\Select::buildFields
	 */
	public function testBuildFieldsFromStringWithoutMeta()
	{
		$criteria = new Criteria\Select;
		$criteria->fields = 'abc_def, ghi_jkl';
		
		$meta = null;
		$fields = $criteria->buildFields($meta);
		$this->assertEquals('abc_def, ghi_jkl', $fields);
	}

	/**
	 * @group unit
	 * @covers Amiss\Criteria\Select::buildFields
	 */
	public function testBuildFieldsFromArrayWithMeta()
	{
		$criteria = new Criteria\Select;
		$criteria->fields = array('foo', 'bar');
		
		$meta = $this->createGenericMeta();
		$fields = $criteria->buildFields($meta);
		$this->assertEquals('`foo_field`, `bar_field`', $fields);
	}

	/**
	 * @group unit
	 * @covers Amiss\Criteria\Select::buildFields
	 */
	public function testBuildFieldsFromArrayWithIncompleteMeta()
	{
		$criteria = new Criteria\Select;
		$criteria->fields = array('foo', 'bar');
		
		$meta = new \Amiss\Meta('stdClass', 'std_class', array(
			'fields'=>array(
				'foo'=>array('name'=>'foo_field'),
			),
		));
		$fields = $criteria->buildFields($meta);
		$this->assertEquals('`foo_field`, `bar`', $fields);
	}

	/**
	 * @group unit
	 * @covers Amiss\Criteria\Select::buildFields
	 */
	public function testBuildFieldsWithPrefix()
	{
		$criteria = new Criteria\Select;
		$criteria->fields = array('foo', 'bar');
		
		$meta = null;
		$fields = $criteria->buildFields($meta, 'whoopee');
		$this->assertEquals('whoopee.`foo`, whoopee.`bar`', $fields);
	}

	/**
	 * @group unit
	 * @covers Amiss\Criteria\Select::buildFields
	 */
	public function testBuildFieldsWithNoFieldsOrMeta()
	{
		$criteria = new Criteria\Select;
		$criteria->fields = null;
		
		$meta = null;
		$fields = $criteria->buildFields($meta);
		$this->assertEquals('*', $fields);
	}

	/**
	 * @group unit
	 * @covers Amiss\Criteria\Select::buildOrder
	 */
	public function testBuildOrderWithNoFieldsOrMeta()
	{
		$criteria = new Criteria\Select;
		$criteria->order = null;
		
		$meta = null;
		$fields = $criteria->buildOrder($meta);
		$this->assertEquals('', $fields);
	}

	/**
	 * @group unit
	 * @covers Amiss\Criteria\Select::buildOrder
	 */
	public function testBuildOrderWithNoFieldsAndEmptyMeta()
	{
		$criteria = new Criteria\Select;
		$criteria->order = null;
		
		$meta = new \Amiss\Meta('stdClass', 'std_class', array());
		$fields = $criteria->buildOrder($meta);
		$this->assertEquals('', $fields);
	}

	/**
	 * @group unit
	 * @covers Amiss\Criteria\Select::buildOrder
	 */
	public function testBuildOrderWithNoFieldsAndFullMeta()
	{
		$criteria = new Criteria\Select;
		$criteria->order = null;
		
		$meta = $this->createGenericMeta();
		$fields = $criteria->buildOrder($meta);
		$this->assertEquals('', $fields);
	}
	
	/**
	 * @group unit
	 * @covers Amiss\Criteria\Select::buildOrder
	 */
	public function testBuildOrderFromStringWithoutMeta()
	{
		$criteria = new Criteria\Select;
		$criteria->order = 'abc_def, ghi_jkl desc';
		
		$meta = null;
		$fields = $criteria->buildOrder($meta);
		$this->assertEquals('abc_def, ghi_jkl desc', $fields);
	}

	/**
	 * @group unit
	 * @covers Amiss\Criteria\Select::buildOrder
	 */
	public function testBuildOrderFromArrayWithoutMeta()
	{
		$criteria = new Criteria\Select;
		$criteria->order = array('abc_def', 'ghi_jkl');
		
		$meta = null;
		$fields = $criteria->buildOrder($meta);
		$this->assertEquals('`abc_def`, `ghi_jkl`', $fields);
	}
	
	/**
	 * @group unit
	 * @covers Amiss\Criteria\Select::buildOrder
	 */
	public function testBuildOrderFromArrayWithEmptyMeta()
	{
		$criteria = new Criteria\Select;
		$criteria->order = array('abc_def', 'ghi_jkl');
		
		$meta = new \Amiss\Meta('stdClass', 'std_class', array());
		$fields = $criteria->buildOrder($meta);
		$this->assertEquals('`abc_def`, `ghi_jkl`', $fields);
	}
	
	/**
	 * @group unit
	 * @covers Amiss\Criteria\Select::buildOrder
	 * @dataProvider dataForBuildOrderFromArrayWithMeta
	 */
	public function testBuildOrderFromArrayWithMeta($order, $expected)
	{
		$criteria = new Criteria\Select;
		$criteria->order = $order;
		
		$meta = $this->createGenericMeta();
		$fields = $criteria->buildOrder($meta);
		$this->assertEquals($expected, $fields);
	}
	
	public function dataForBuildOrderFromArrayWithMeta()
	{
		return array(
			array(array('foo', 'bar'=>'asc'), '`foo_field`, `bar_field`'),
			array(array('foo'=>'asc', 'bar'=>'desc'), '`foo_field`, `bar_field` desc'),
			array(array('foo'=>'desc', 'bar'), '`foo_field` desc, `bar_field`'),
		);
	}

	/**
	 * @group unit
	 * @covers Amiss\Criteria\Select::buildOrder
	 */
	public function testBuildOrderFromArrayWithIncompleteMeta()
	{
		$criteria = new Criteria\Select;
		$criteria->order = array('foo', 'bar');
		
		$meta = new \Amiss\Meta('stdClass', 'std_class', array(
			'fields'=>array(
				'foo'=>array('name'=>'foo_field'),
			),
		));
		$fields = $criteria->buildOrder($meta);
		$this->assertEquals('`foo_field`, `bar`', $fields);
	}
	
	protected function createGenericMeta()
	{
		return new \Amiss\Meta('stdClass', 'std_class', array(
			'fields'=>array(
				'foo'=>array('name'=>'foo_field'),
				'bar'=>array('name'=>'bar_field'),
			),
		));
	}
}
