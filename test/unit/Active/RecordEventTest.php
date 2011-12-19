<?php

namespace Amiss\Test\Unit\Active;

use Amiss\Active\Record;

class RecordEventTest extends \CustomTestCase
{
	public function setUp()
	{
		$this->manager = $this->getMock(
			'Amiss\Manager',
			array('insert', 'update', 'delete', 'save'),
			array(),
			'',
			!'callOriginalConstructor'
		);
		
		RecordEventTestRecord::setManager($this->manager);
	}

	/**
	 * @covers Amiss\Active\Record::beforeUpdate
	 */
	public function testBeforeUpdate()
	{
		$ret = $this->getMock(__NAMESPACE__.'\RecordEventTestRecord', array('beforeUpdate'));
		$ret->expects($this->once())->method('beforeUpdate');
		$this->manager->expects($this->once())->method('update');
		$ret->update();
	}

	/**
	 * @covers Amiss\Active\Record::beforeInsert
	 */
	public function testBeforeInsert()
	{
		$ret = $this->getMock(__NAMESPACE__.'\RecordEventTestRecord', array('beforeInsert'));
		$ret->expects($this->once())->method('beforeInsert');
		$this->manager->expects($this->once())->method('insert');
		$ret->insert();
	}
	
	/**
	 * @covers Amiss\Active\Record::delete
	 */
	public function testBeforeDelete()
	{
		$ret = $this->getMock(__NAMESPACE__.'\RecordEventTestRecord', array('beforeDelete'));
		$ret->expects($this->once())->method('beforeDelete');
		$this->manager->expects($this->once())->method('delete');
		$ret->delete();
	}
	
	/**
	 * @covers Amiss\Active\Record::beforeSave
	 */
	public function testBeforeSaveCalledOnInsert()
	{
		$ret = $this->getMock(__NAMESPACE__.'\RecordEventTestRecord', array('beforeSave'));
		$ret->expects($this->once())->method('beforeSave');
		$this->manager->expects($this->once())->method('insert');
		$ret->insert();
	}
	
	/**
	 * @covers Amiss\Active\Record::beforeSave
	 */
	public function testBeforeUpdateCalledOnUpdate()
	{
		$ret = $this->getMock(__NAMESPACE__.'\RecordEventTestRecord', array('beforeSave'));
		$ret->expects($this->once())->method('beforeSave');
		$this->manager->expects($this->once())->method('update');
		$ret->update();
	}
}

class RecordEventTestRecord extends Record
{
	
}
