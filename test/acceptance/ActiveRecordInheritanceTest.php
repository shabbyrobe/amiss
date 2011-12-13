<?php

/*
 * This file is part of Amiss.
 * 
 * Amiss is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * Amiss is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with Amiss.  If not, see <http://www.gnu.org/licenses/>.
 * 
 * Copyright 2011 Blake Williams
 * http://k3jw.com 
 */

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
