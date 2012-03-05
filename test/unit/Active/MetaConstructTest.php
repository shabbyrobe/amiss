<?php

namespace Amiss\Test\Unit\Active;

use Amiss\Active\Meta;
use Amiss\Active\Record;
use Amiss\Active\TypeHandler;

class MetaConstructTest extends \CustomTestCase
{
	/**
	 * @group active
	 * @covers Amiss\Active\Meta::__construct
	 */
	public function testConstructWithGetTableNameMethod()
	{
		$meta = new Meta(__NAMESPACE__.'\MetaTestGetTableNameMethod');
		$this->assertEquals($meta->table, 'foooo');
	}
	
	/**
	 * @group active
	 * @covers Amiss\Active\Meta::__construct
	 */
	public function testConstructWithGetTableNameField()
	{
		$meta = new Meta(__NAMESPACE__.'\MetaTestGetTableNameField');
		$this->assertEquals($meta->table, 'bar');
	}
}

class MetaTestGetTableNameMethod extends Record
{
	public static function getTableName()
	{
		return "foooo";
	}
}

class MetaTestGetTableNameField extends Record
{
	public static $table = 'bar';
}
