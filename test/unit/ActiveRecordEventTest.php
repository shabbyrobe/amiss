<?php

namespace Amiss\Test\Unit;

use Amiss\Active\Record;

class ActiveRecordEventTest extends \CustomTestCase
{
	public function setUp()
	{
		$this->connector = $this->getMock('Amiss\Connector', array(), array(), '', !'callOriginalConstructor');
		
		$this->mapper = new \Amiss\Mapper\Note;
		
		$this->manager = $this->getMock(
			'Amiss\Manager',
			array('insert', 'update', 'delete'),
			array($this->connector, $this->mapper)
		);
		
		RecordEventTestRecord::setManager($this->manager);
	}

	/**
	 * @covers Amiss\Active\Record::beforeUpdate
	 * @group active
	 * @group unit
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
	 * @group active
	 * @group unit
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
	 * @group active
	 * @group unit
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
	 * @group active
	 * @group unit
	 */
	public function testBeforeSaveCalledOnInsert()
	{
		$ret = $this->getMock(__NAMESPACE__.'\RecordEventTestRecord', array('beforeSave'), array(), 'PHPUnitGotcha_BeforeSaveCalledOnInsert');
		$ret->expects($this->once())->method('beforeSave');
		$this->manager->expects($this->once())->method('insert');
		$ret->insert();
	}
	
	/**
	 * @covers Amiss\Active\Record::beforeSave
	 * @group active
	 */
	public function testBeforeSaveCalledOnUpdate()
	{
		$ret = $this->getMock(__NAMESPACE__.'\RecordEventTestRecord', array('beforeSave'), array(), 'PHPUnitGotcha_BeforeSaveCalledOnUpdate');
		$ret->expects($this->once())->method('beforeSave');
		$this->manager->expects($this->once())->method('update');
		$ret->update();
	}
	/**
	 * @covers Amiss\Active\Record::beforeInsert
	 * @group active
	 * @group unit
	 */
	public function testBeforeInsertCalledOnSave()
	{
		$ret = $this->getMock(__NAMESPACE__.'\RecordEventTestRecord', array('beforeSave'), array(), 'PHPUnitGotcha_BeforeInsertCalledOnSave');
		$ret->expects($this->once())->method('beforeSave');
		$this->manager->expects($this->once())->method('insert');
		$ret->id = null;
		$ret->save();
	}
	
	/**
	 * @covers Amiss\Active\Record::beforeUpdate
	 * @group active
	 */
	public function testBeforeUpdateCalledOnSave()
	{
		$ret = $this->getMock(__NAMESPACE__.'\RecordEventTestRecord', array('beforeSave'), array(), 'PHPUnitGotcha_BeforeUpdateCalledOnSave');
		$ret->expects($this->once())->method('beforeSave');
		$this->manager->expects($this->once())->method('update');
		$ret->id = 1;
		$ret->save();
	}
}

class RecordEventTestRecord extends Record
{
	/** 
	 * @primary
	 * @type autoinc 
	 */
	public $id;
}
