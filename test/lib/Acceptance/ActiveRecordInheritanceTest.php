<?php
namespace Amiss\Test\Acceptance;

use Amiss\Demo\Active;

/**
 * @group active
 * @group acceptance
 */
class ActiveRecordInheritanceTest extends \Amiss\Test\Helper\TestCase
{
    public function setUp()
    {
        $this->deps = \Amiss\Test\Factory::managerActiveDemo();
    }
    
    public function testSelect()
    {
        $event = Active\PlannedEvent::getById(1);
        $this->assertEquals('AwexxomeFest 2025', $event->name);
        $this->assertEquals(20, $event->completeness);
    }
    
    public function testFieldInheritance()
    {
        $meta = Active\PlannedEvent::getMeta();
        $fields = $meta->fields;
        $this->assertArrayHasKey('name', $fields);
        $this->assertArrayHasKey('completeness', $fields);
    }
}
