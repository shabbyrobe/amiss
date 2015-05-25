<?php
namespace Amiss\Test\Unit;

use Amiss\Sql\ActiveRecord;

/**
 * @group unit
 */
class ActiveRecordEventTest extends \Amiss\Test\Helper\TestCase
{
    public function setUp()
    {
        parent::setUp();
        
        $this->connector = $this->getMock('PDOK\Connector', array(), array(), '', !'callOriginalConstructor');
        
        $this->mapper = new \Amiss\Mapper\Note;
        
        $this->manager = $this->getMock(
            'Amiss\Sql\Manager',
            array('insert', 'update', 'delete'),
            array($this->connector, $this->mapper)
        );
        
        RecordEventTestRecord::setManager($this->manager);
    }

    /**
     * @covers Amiss\Sql\ActiveRecord::beforeUpdate
     * @group active
     */
    public function testBeforeUpdate()
    {
        $ret = $this->getMock(__NAMESPACE__.'\RecordEventTestRecord', array('beforeUpdate'));
        $ret->expects($this->once())->method('beforeUpdate');
        $this->manager->expects($this->once())->method('update');
        $ret->update();
    }

    /**
     * @covers Amiss\Sql\ActiveRecord::beforeInsert
     * @group active
     */
    public function testBeforeInsert()
    {
        $ret = $this->getMock(__NAMESPACE__.'\RecordEventTestRecord', array('beforeInsert'));
        $ret->expects($this->once())->method('beforeInsert');
        $this->manager->expects($this->once())->method('insert');
        $ret->insert();
    }
    
    /**
     * @covers Amiss\Sql\ActiveRecord::delete
     * @group active
     */
    public function testBeforeDelete()
    {
        $ret = $this->getMock(__NAMESPACE__.'\RecordEventTestRecord', array('beforeDelete'));
        $ret->expects($this->once())->method('beforeDelete');
        $this->manager->expects($this->once())->method('delete');
        $ret->delete();
    }

    /**
     * @covers Amiss\Sql\ActiveRecord::beforeSave
     * @group active
     */
    public function testBeforeSaveCalledOnInsert()
    {
        $ret = $this->getMock(__NAMESPACE__.'\RecordEventTestRecord', array('beforeSave'), array(), 'PHPUnitGotcha_BeforeSaveCalledOnInsert');
        $ret->expects($this->once())->method('beforeSave');
        $this->manager->expects($this->once())->method('insert');
        $ret->insert();
    }
    
    /**
     * @covers Amiss\Sql\ActiveRecord::beforeSave
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
     * @covers Amiss\Sql\ActiveRecord::beforeInsert
     * @group active
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
     * @covers Amiss\Sql\ActiveRecord::beforeUpdate
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

class RecordEventTestRecord extends ActiveRecord
{
    /** :amiss = {"field": { "primary": true, "type": "autoinc" }}; */
    public $id;
}
