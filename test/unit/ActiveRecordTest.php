<?php
namespace Amiss\Test\Unit;

/**
 * @group unit
 */
class ActiveRecordTest extends \DataTestCase
{
    public function setUp()
    {
        parent::setUp();
        
        \Amiss\Sql\ActiveRecord::_reset();
        $this->db = $this->getConnector();
        $this->mapper = new \Amiss\Mapper\Note;
        $this->mapper->objectNamespace = 'Amiss\Demo\Active';
        $this->manager = new \Amiss\Sql\Manager($this->db, $this->mapper);
    }
    
    /**
     * @covers Amiss\Sql\ActiveRecord::getMeta
     * @group active
     */
    public function testGetMeta()
    {
        \Amiss\Sql\ActiveRecord::setManager($this->manager);
        $meta = TestActiveRecord1::getMeta();
        $this->assertInstanceOf('Amiss\Meta', $meta);
        $this->assertEquals(__NAMESPACE__.'\TestActiveRecord1', $meta->class);
        
        // ensure the instsance is cached
        $this->assertTrue($meta === TestActiveRecord1::getMeta());
    }
        
    /**
     * @group active
     * @covers Amiss\Sql\ActiveRecord::getManager
     * @covers Amiss\Sql\ActiveRecord::setManager
     */
    public function testMultiConnection()
    {
        \Amiss\Sql\ActiveRecord::setManager($this->manager);
        $manager2 = clone $this->manager;
        $this->assertFalse($this->manager === $manager2);
        OtherConnBase::setManager($manager2);
        
        $c1 = TestOtherConnRecord1::getManager();
        $c2 = TestOtherConnRecord2::getManager();
        $this->assertTrue($c1 === $c2);
        
        $c3 = TestActiveRecord1::getManager();
        $this->assertFalse($c1 === $c3); 
    }
    
    /**
     * @covers Amiss\Sql\ActiveRecord::__callStatic
     * @group active
     */
    public function testGetForwarded()
    {
        $manager = $this->getMock('Amiss\Sql\Manager', array('get'), array($this->db, $this->mapper));
        $manager->expects($this->once())->method('get')->with(
            $this->equalTo(__NAMESPACE__.'\TestActiveRecord1'), 
            $this->equalTo('pants=?'), 
            $this->equalTo(1)
        );
        \Amiss\Sql\ActiveRecord::setManager($manager);
        $tar = new TestActiveRecord1;
        TestActiveRecord1::get('pants=?', 1);
    }
    
    /**
     * @covers Amiss\Sql\ActiveRecord::__callStatic
     * @group active
     * @group unit
     */
    public function testGetById()
    {
        $manager = $this->getMock('Amiss\Sql\Manager', array('getById'), array($this->db, $this->mapper));
        \Amiss\Sql\ActiveRecord::setManager($manager);
        
        $manager->expects($this->once())->method('getById')->with(
            $this->equalTo(__NAMESPACE__.'\TestActiveRecord1'), 
            $this->equalTo(1)
        );
        TestActiveRecord1::getById(1);
    }
    
    /**
     * @covers Amiss\Sql\ActiveRecord::__callStatic
     * @group active
     * @group unit
     */
    public function testGetRelated()
    {
        $this->mapper->objectNamespace = 'Amiss\Test\Unit\Active';
        
        $manager = $this->getMock('Amiss\Sql\Manager', array('getRelated'), array($this->db, $this->mapper));
        \Amiss\Sql\ActiveRecord::setManager($manager);
        
        $manager->expects($this->once())->method('getRelated')->with(
            $this->isInstanceOf(__NAMESPACE__.'\TestRelatedChild'),
            $this->equalTo('parent')
        )->will($this->returnValue(999));
        
        $child = new TestRelatedChild;
        $child->childId = 6;
        $child->parentId = 1;
        $result = $child->getRelated('parent');
        $this->assertEquals(999, $result);
    }
    
    /**
     * If a record has not been loaded from the database and the class doesn't
     * define fields, undefined properties should throw
     * 
     * @covers Amiss\Sql\ActiveRecord::__get
     * @group active
     * @group unit
     * @expectedException BadMethodCallException
     */
    public function testGetUnknownPropertyWhenFieldsUndefinedOnNewObjectReturnsNull()
    {
        TestActiveRecord1::setManager($this->manager);
        $ar = new TestActiveRecord1();
        $a = $ar->thisPropertyShouldNeverExist;
    }
    
    /**
     * If the class defines its fields, undefined properties should always throw. 
     * 
     * @covers Amiss\Sql\ActiveRecord::__get
     * @group active
     * @expectedException BadMethodCallException
     */
    public function testGetUnknownPropertyWhenFieldsDefinedThrowsException()
    {
        TestActiveRecord1::setManager($this->manager);
        $ar = new TestActiveRecord1();
        $value = $ar->thisPropertyShouldNeverExist;
    }
    
    /**
     * Even if the class doesn't define its fields, undefined properties should throw
     * if the record has been loaded from the database as we can expect it is fully
     * populated.
     * 
     * @group active
     * @expectedException BadMethodCallException
     */
    public function testGetUnknownPropertyWhenFieldsUndefinedAfterRetrievingFromDatabaseThrowsException()
    {
        TestActiveRecord1::setManager($this->manager);
        $this->db->query("CREATE TABLE table_1(fooBar STRING);");
        $this->db->query("INSERT INTO table_1(fooBar) VALUES(123)");
        
        $ar = TestActiveRecord1::get('fooBar=123');
        $value = $ar->thisPropertyShouldNeverExist;
    }
    
    /**
     * @group active
     */
    public function testUpdateTable()
    {
        $manager = $this->getMock('Amiss\Sql\Manager', array('update'), array($this->db, $this->mapper), 'PHPUnitGotcha_RecordTest_'.__FUNCTION__);
        $manager->expects($this->once())->method('update')->with(
            $this->equalTo(__NAMESPACE__.'\TestActiveRecord1'), 
            $this->equalTo(array('pants'=>1)),
            $this->equalTo(1)
        );
        TestActiveRecord1::setManager($manager);
        TestActiveRecord1::updateTable(array('pants'=>1), '1');
    }
}

/**
 * @table table_1
 */
class TestActiveRecord1 extends \Amiss\Sql\ActiveRecord
{
    /** @primary */
    public $fooBar;
}

/**
 * @table table_2
 */
class TestActiveRecord2 extends \Amiss\Sql\ActiveRecord
{
    /** @primary */
    public $testActiveRecord2Id;
}

class TestActiveRecord3 extends \Amiss\Sql\ActiveRecord
{
    /** @primary */
    public $testActiveRecord3Id;
}

abstract class OtherConnBase extends \Amiss\Sql\ActiveRecord {}

class TestOtherConnRecord1 extends OtherConnBase {}

class TestOtherConnRecord2 extends OtherConnBase {}

class TestRelatedParent extends \Amiss\Sql\ActiveRecord
{
    /** @primary */
    public $parentId;
    
    /**
     * @has.many.of TestRelatedChild
     */
    public $children;
}

class TestRelatedChild extends \Amiss\Sql\ActiveRecord
{
    /** @primary */
    public $childId;
    
    /** @field */
    public $parentId;
    
    /**
     * @has.one.of TestRelatedParent
     * @has.one.on parentId
     */
    public $parent;
}
