<?php

namespace Amiss\Test\Unit\Active;

class RecordExportRowTest extends \CustomTestCase
{
	public function setUp()
	{
		\Amiss\Active\Record::_reset();
		$this->connector = new \TestConnector('sqlite::memory:');
		$this->manager = new \Amiss\Manager($this->connector);
		\Amiss\Active\Record::setManager($this->manager);
	}
	
	/**
	 * @covers Amiss\Active\Record::exportRow
	 */
	public function testExportRowUsesDefaultTypeHandlerWhenFieldTypeNotSpecified()
	{
		$r = new RecordExportRowUsesDefaultRecord();
		
		$r->pants = 'yep';
		$expected = 'value';
		
		RecordExportRowUsesDefaultRecord::addTypeHandler(
			new \TestTypeHandler(array('valueForDb'=>$expected)),
			'foo'
		);
		$row = $r->exportRow();
		
		$this->assertEquals('value', $row['pants']);
	}
}

class RecordExportRowUsesDefaultRecord extends \Amiss\Active\Record
{
	public static $defaultFieldType='foo';
	
	public static $primary = 'id';
	
	public $id;
	
	public static $fields = array(
		'pants',
	);
}
