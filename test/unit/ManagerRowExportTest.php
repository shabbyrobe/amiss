<?php

namespace Amiss\Test\Unit;

use Amiss\Manager;

class ManagerRowExportTest extends \CustomTestCase
{
	public function setUp()
	{
		$this->manager = new Manager(array());
	}
	
	/**
	 * @expectedException Amiss\Exception
	 * @covers Amiss\Manager::exportRow
	 */
	public function testCustomExportRow()
	{
		$obj = new CustomBorkedRowExporter();
		$values = $this->callProtected($this->manager, 'exportRow', $obj);
	}
	
	/**
	 * @covers Amiss\Manager::exportRow
	 */
	public function testCustomExportRowFailsWhenArrayNotReturned()
	{
		$obj = new CustomRowExporter();
		$values = $this->callProtected($this->manager, 'exportRow', $obj);
		$this->assertEquals(array('custom'=>'row'), $values);
	}
	
	/**
	 * @covers Amiss\Manager::exportRow
	 */
	public function testDefaultExportRowIgnoresNulls()
	{
		$obj = (object)array(
			'null'=>null,
		);
		$values = $this->callProtected($this->manager, 'exportRow', $obj);
		$this->assertEquals(array(), $values);
	}

	/**
	 * @covers Amiss\Manager::exportRow
	 */
	public function testDefaultExportRowDoesntIgnoreFalse()
	{
		$obj = (object)array(
			'false'=>false,
		);
		$values = $this->callProtected($this->manager, 'exportRow', $obj);
		$this->assertEquals(array('false'=>false), $values);
	}

	/**
	 * @covers Amiss\Manager::exportRow
	 */
	public function testDefaultExportRowIgnoresObject()
	{
		$child = new \stdClass;
		$obj = (object)array(
			'object'=>$child,
		);
		$values = $this->callProtected($this->manager, 'exportRow', $obj);
		$this->assertEquals(array(), $values);
	}

	/**
	 * @covers Amiss\Manager::exportRow
	 */
	public function testDefaultExportRowIgnoresArray()
	{
		$child = new \stdClass;
		$obj = (object)array(
			'array'=>array('a'),
		);
		$values = $this->callProtected($this->manager, 'exportRow', $obj);
		$this->assertEquals(array(), $values);
	}

	/**
	 * @covers Amiss\Manager::exportRow
	 */
	public function testDefaultExportRowDoesntIgnoreScalars()
	{
		$child = new \stdClass;
		$obj = (object)array(
			'int'=>1,
			'string'=>'string',
			'float'=>1.1,
			'bool'=>true,
		);
		$values = $this->callProtected($this->manager, 'exportRow', $obj);
		$this->assertEquals(
			array(
				'int'=>1,
				'string'=>'string',
				'float'=>1.1,
				'bool'=>true
			),
			$values
		);
	}
	
	public function testDocExampleWorks()
	{
		$obj = new CustomRowExporterDocExample;
		$obj->id = 1;
		$obj->name = 'foo';
		$obj->anObject = (object)array('yep'=>'yeppo');
		
		$values = $this->callProtected($this->manager, 'exportRow', $obj);
		$this->assertEquals(
			array(
				'id'=>1,
				'name'=>'foo',
				'anObject'=>'O:8:"stdClass":1:{s:3:"yep";s:5:"yeppo";}',
				'setNull'=>null
			),
			$values
		);
	}
}

class CustomRowExporter implements \Amiss\RowExporter
{
	public function exportRow()
	{
		return array('custom'=>'row');
	}
}

class CustomBorkedRowExporter implements \Amiss\RowExporter
{
	public function exportRow()
	{
		return 12345;
	}
}

class CustomRowExporterDocExample implements \Amiss\RowExporter
{
	public $id;
	public $name;
	public $anObject;
	public $setNull;
	
	public function exportRow()
	{
		$values = (array)$this;
		$values['anObject'] = serialize($values['anObject']);
		return $values;
	}
}
