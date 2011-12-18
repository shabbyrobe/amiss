<?php

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
