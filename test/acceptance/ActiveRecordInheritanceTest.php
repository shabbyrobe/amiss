<?php

namespace Amiss\Test\Acceptance;

use Amiss\Demo\Active;

class ActiveRecordInheritanceTest extends \SqliteDataTestCase
{
	public function setUp()
	{
		parent::setUp();
		$this->manager->objectNamespace = 'Amiss\Demo\Active';
		\Amiss\Active\Record::_reset();
		\Amiss\Active\Record::setManager($this->manager);
	}
	
	public function testSelect()
	{
		$event = Active\PlannedEvent::getByPk(1);
		$this->assertEquals('AwexxomeFest 2025', $event->name);
		$this->assertEquals(20, $event->completeness);
	}
	
	public function testFieldInheritance()
	{
		$meta = Active\PlannedEvent::getMeta();
		$fields = $meta->getFields();
		$this->assertArrayHasKey('name', $fields);
		$this->assertArrayHasKey('completeness', $fields);
	}
}
