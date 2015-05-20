<?php
namespace Amiss\Test\Acceptance;

use Amiss\Demo\Active;

class MapperTypeHandlerAcceptanceTest extends \ActiveRecordDataTestCase
{
    public function setUp()
    {
        parent::setUp();
        \Amiss\Sql\ActiveRecord::_reset();
        \Amiss\Sql\ActiveRecord::setManager($this->manager);
    }
    
    /**
     * @group acceptance
     * @group mapper
     */
    public function testCustomType()
    {
        $this->mapper->addTypeHandler(new TestCustomFieldTypeHandler(), 'foo');
        $this->createRecordMemoryDb(__NAMESPACE__.'\TestCustomFieldTypeRecord');
        
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
        $this->mapper->addTypeHandler(new TestTypeHandler(), 'varchar');
        $event = \Amiss\Demo\Active\EventRecord::getById(1);
        $this->assertEquals('zAwexxomeFestz', $event->name);
    }
    
    /**
     * @group acceptance
     * @group mapper
     */
    public function testTypeMapperOnSave()
    {
        $this->mapper->addTypeHandler(new TestTypeHandler(), 'varchar');
        $event = \Amiss\Demo\Active\EventRecord::getById(1);
        
        $event->save();
        $event = \Amiss\Demo\Active\EventRecord::getById(1);
        $this->assertEquals('zzHANDLEDzz', $event->name);
    }
}

class TestTypeHandler implements \Amiss\Type\Handler
{
    public $garbage;
    
    public function __construct($garbage='z')
    {
        $this->garbage = $garbage;
    }
    
    public function prepareValueForDb($value, array $fieldInfo)
    {
        return $this->garbage.'HANDLED'.$this->garbage;
    }
    
    public function handleValueFromDb($value, array $fieldInfo, $row)
    {
        return $this->garbage.$value.$this->garbage;
    }
    
    function createColumnType($engine, array $fieldInfo)
    {
        return "TEXT";
    }
}

class TestCustomFieldTypeRecord extends \Amiss\Sql\ActiveRecord
{
    /** :amiss = {"field": {"primary": true, "type": "autoinc" }}; */
    public $testCustomFieldTypeRecordId;
    
    /**
     * :amiss = {"field": {"type": "foo bar"}};
     */
    public $yep1;
}

class TestCustomFieldTypeHandler implements \Amiss\Type\Handler
{
    function prepareValueForDb($value, array $fieldInfo)
    {
        return "db-$value";
    }
    
    function handleValueFromDb($value, array $fieldInfo, $row)
    {
        return "value-$value"; 
    }
    
    function createColumnType($engine, array $fieldInfo)
    {
        return "TEXT";
    }
}
