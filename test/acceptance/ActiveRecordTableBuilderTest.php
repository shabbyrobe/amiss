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

use Amiss\Active\TableBuilder,
	Amiss\Demo\Active
;

class ActiveRecordTableBuilderTest extends \SqliteDataTestCase
{
	public function testCreateTable()
	{
		$db = new \Amiss\Connector('sqlite::memory:');
		$manager = new \Amiss\Manager($db);
		
		\Amiss\Active\Record::_reset();
		\Amiss\Active\Record::setManager($manager);
		
		$tableBuilder = new TableBuilder('Amiss\Demo\Active\EventRecord');
		$tableBuilder->createTable();
		
		$er = new Active\EventRecord();
		$er->name = 'foo bar';
		$er->slug = 'foobar';
		$er->save();
	}
}
