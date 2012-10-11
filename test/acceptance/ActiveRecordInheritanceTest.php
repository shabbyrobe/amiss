<?php
namespace Amiss\Test\Acceptance;

use Amiss\Demo\Active;

class ActiveRecordInheritanceTest extends \ActiveRecordDataTestCase
{
    /**
     * @group active
     * @group acceptance
     */
    public function setUp()
    {
        parent::setUp();
        \Amiss\Sql\ActiveRecord::_reset();
        \Amiss\Sql\ActiveRecord::setManager($this->manager);
    }
    
    /**
     * @group active
     * @group acceptance
     */
    public function testSelect()
    {
        $event = Active\PlannedEvent::getById(1);
        $this->assertEquals('AwexxomeFest 2025', $event->name);
        $this->assertEquals(20, $event->completeness);
    }
    
    /**
     * @group active
     * @group acceptance
     */
    public function testFieldInheritance()
    {
        $meta = Active\PlannedEvent::getMeta();
        $fields = $meta->getFields();
        $this->assertArrayHasKey('name', $fields);
        $this->assertArrayHasKey('completeness', $fields);
    }
}
