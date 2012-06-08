<?php

namespace Amiss\Test\Acceptance;

use Amiss\Demo\Active;

class MapperTypeHandlerAcceptanceTest extends \ActiveRecordDataTestCase
{
    public function setUp()
    {
        parent::setUp();
        \Amiss\Active\Record::_reset();
        \Amiss\Active\Record::setManager($this->manager);
    }
    
    /**
     * @group acceptance
     * @group mapper
     */
    public function testCustomType()
    {
        $this->createRecordMemoryDb(__NAMESPACE__.'\TestCustomFieldTypeRecord');
        $this->mapper->addTypeHandler(new TestCustomFieldTypeHandler(), 'foo');
        
        $r = new TestCustomFieldTypeRecord;
        $r->yep1 = 'foo';
        $r->save();
        
        $r = TestCustomFieldTypeRecord::getById(1);
        
        // this will have passed through the prepareValueForDb first, then
        // through the handleValueFromDb method
        $this->assertEquals('value-db-foo', $r->yep1);
    }

    /**
     * @group acceptance
     * @group mapper
     */
    public function testTypeMapperOnRetrieve()
    {
        $this->mapper->addTypeHandler(new TestTypeHandler(), 'datetime');
        $event = \Amiss\Demo\Active\EventRecord::getById(1);
        $this->assertEquals('z1936-01-01z', $event->dateStart);
        $this->assertEquals('z1936-01-02z', $event->dateEnd);
    }
    
    /**
     * @group acceptance
     * @group mapper
     */
    public function testTypeMapperOnSave()
    {
        $this->mapper->addTypeHandler(new TestTypeHandler(), 'datetime');
        $event = \Amiss\Demo\Active\EventRecord::getById(1);
        
        $event->save();
        $event = \Amiss\Demo\Active\EventRecord::getById(1);
        $this->assertEquals('zz2001-01-01 15:15:15zz', $event->dateStart);
        $this->assertEquals('zz2001-01-01 15:15:15zz', $event->dateEnd);
    }
}

class TestTypeHandler implements \Amiss\Type\Handler
{
    public $garbage;
    
    public function __construct($garbage='z')
    {
        $this->garbage = $garbage;
    }
    
    public function prepareValueForDb($value, $object, array $fieldInfo)
    {
        return $this->garbage.'2001-01-01 15:15:15'.$this->garbage;
    }
    
    public function handleValueFromDb($value, $object, array $fieldInfo, $row)
    {
        return $this->garbage.$value.$this->garbage;
    }
    
    function createColumnType($engine)
    {}
}

class TestCustomFieldTypeRecord extends \Amiss\Active\Record
{
    /**
     * @primary
     * @type autoinc
     */
    public $testCustomFieldTypeRecordId;
    
    /**
     * @field
     * @type foo bar
     */
    public $yep1;
}

class TestCustomFieldTypeHandler implements \Amiss\Type\Handler
{
    function prepareValueForDb($value, $object, array $fieldInfo)
    {
        return "db-$value";
    }
    
    function handleValueFromDb($value, $object, array $fieldInfo, $row)
    {
        return "value-$value"; 
    }
    
    function createColumnType($engine)
    {}
}
