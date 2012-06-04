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
        \Amiss\Active\Record::_reset();
        \Amiss\Active\Record::setManager($this->manager);
    }
    
    /**
     * @group active
     * @group acceptance
     */
    public function testSelect()
    {
        $event = Active\PlannedEvent::getByPk(1);
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
