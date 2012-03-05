<?php

namespace Amiss\Test\Unit\Active;

use Amiss\Active\Meta;
use Amiss\Active\Record;
use Amiss\Active\TypeHandler;

class MetaGetRelationsTest extends \CustomTestCase
{
	/**
	 * @covers Amiss\Active\Meta::getRelations
	 * @group active
	 */
	function testGetRelations()
	{
		$meta = new Meta(__NAMESPACE__.'\MetaGetRelationsRecord');
		$relations = $meta->getRelations();
		
		$expected = array(
			'foo'=>array('one'=>'Foo', 'on'=>'fooId'),
		);
		$this->assertEquals($expected, $relations);
	}
	
	/**
	 * @covers Amiss\Active\Meta::getRelations
	 * @group active
	 */
	function testGetChildWithRelationsOverridesParent()
	{
		$parent = new Meta(__NAMESPACE__.'\MetaGetRelationsParentRecord');
		$meta = new Meta(__NAMESPACE__.'\MetaGetRelationsRecord', $parent);
		$relations = $meta->getRelations();
		
		$expected = array(
			'foo'=>array('one'=>'Foo', 'on'=>'fooId'),
		);
		$this->assertEquals($expected, $relations);
	}

	/**
	 * @covers Amiss\Active\Meta::getRelations
	 * @group active
	 */
	function testGetChildWithoutRelationsReturnsParent()
	{
		$parent = new Meta(__NAMESPACE__.'\MetaGetRelationsParentRecord');
		$meta = new Meta(__NAMESPACE__.'\MetaGetNoRelationsRecord', $parent);
		$relations = $meta->getRelations();
		
		$expected = array(
			'bar'=>array('one'=>'Bar', 'on'=>'barId'),
		);
		$this->assertEquals($expected, $relations);
	}
}

class MetaGetRelationsParentRecord extends Record
{
	public static $relations = array(
		'bar'=>array('one'=>'Bar', 'on'=>'barId'),
	);
}

class MetaGetRelationsRecord extends MetaGetRelationsParentRecord
{
	public static $relations = array(
		'foo'=>array('one'=>'Foo', 'on'=>'fooId'),
	);
}

class MetaGetNoRelationsRecord extends MetaGetRelationsParentRecord
{
	
}
