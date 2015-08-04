<?php
namespace Amiss\Test\Acceptance;

use Amiss\Demo\Active;
use Amiss\Sql\TableBuilder;

/**
 * @group acceptance
 * @group mapper
 */
class MapperTypeHandlerAcceptanceTest extends \Amiss\Test\Helper\TestCase
{
    public function setUp()
    {
        $this->deps = \Amiss\Test\Factory::managerActiveDemo();

        // this is not ideal - need to add managerActiveCustom
        \Amiss\Sql\ActiveRecord::_reset();
        \Amiss\Sql\ActiveRecord::setManager($this->deps->manager);
    }

    public function tearDown()
    {
        \Amiss\Sql\ActiveRecord::_reset();
        $this->deps = null;
        parent::tearDown();
    }

    public function testCustomType()
    {
        $mapper = \Amiss\Sql\Factory::createMapper();
        $mapper->addTypeHandler(new TestCustomFieldTypeHandler(), 'foo');

        $deps = \Amiss\Test\Factory::managerNoteModelCustom('
            /** :amiss = true; */
            class TestCustomFieldTypeModel
            {
                /** :amiss = {"field": {"primary": true, "type": "autoinc" }}; */
                public $testCustomFieldTypeRecordId;
                
                /**
                 * :amiss = {"field": {"type": "foo bar"}};
                 */
                public $yep1;
            }
        ', (object)['mapper'=>$mapper]);

        $class = $deps->classes['TestCustomFieldTypeModel'];

        $r = new $class;
        $r->yep1 = 'foo';
        $deps->manager->save($r);
        
        $r = $deps->manager->getById($class, 1);
        
        // this will have passed through the prepareValueForDb first, then
        // through the handleValueFromDb method
        $this->assertEquals('value-db-foo', $r->yep1);
    }

    public function testTypeMapperOnRetrieve()
    {
        $this->deps->mapper->addTypeHandler(new TestTypeHandler(), 'varchar');
        $event = \Amiss\Demo\Active\EventRecord::getById(1);
        $this->assertEquals('zAwexxomeFestz', $event->name);
    }
    
    public function testTypeMapperOnSave()
    {
        $this->deps->mapper->addTypeHandler(new TestTypeHandler(), 'varchar');
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
