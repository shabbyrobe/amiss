<?php

namespace Amiss\Test\Unit\Active;

use Amiss\Active\Meta;
use Amiss\Active\Record;
use Amiss\Active\TypeHandler;


/**
 * TODO:
 * 
 * This test case needs to cover all of the ways field merging might work.
 * 
 * Child fields = array('foo', 'bar'), Parent fields = array('foo'=>'type', 'baz')
 * Result = array('foo'=>'type', 'bar', 'baz')
 * 
 * That kind of crap
 *
 */

class MetaGetFieldsTest extends \CustomTestCase
{
	/**
	 * @covers Amiss\Active\Meta::getFields
	 */
	public function testGetFields()
	{
		$meta = new Meta(__NAMESPACE__.'\MetaGetFieldsTestRecord');
		$expected = array('foo', 'bar');
		$this->assertEquals($expected, $meta->getFields());
	}
}

class MetaGetFieldsTestRecord extends Record
{
	public static $fields = array(
		'foo', 
		'bar',
		'baz'=>'type',
	);
}

class MetaGetFieldsChildRecord extends MetaGetFieldsTestRecord
{
	public static $fields = array(
		'a', 
		'b',
		'baz'=>'type',
	);
}
