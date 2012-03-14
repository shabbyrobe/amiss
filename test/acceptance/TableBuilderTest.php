<?php

namespace Amiss\Test\Acceptance;

use Amiss\TableBuilder,
	Amiss\Demo
;

class TableBuilderTest extends \ActiveRecordDataTestCase
{
	/**
	 * @group mapper
	 * @group acceptance
	 */
	public function testCreateTableSqlite()
	{
		$db = new \Amiss\Connector('sqlite::memory:');
		
		$manager = new \Amiss\Manager($db, new \Amiss\Active\Mapper);
		
		\Amiss\Active\Record::_reset();
		\Amiss\Active\Record::setManager($manager);
		
		$tableBuilder = new TableBuilder($manager, 'Amiss\Demo\Active\EventRecord');
		$tableBuilder->createTable();
		
		$er = new Demo\Active\EventRecord();
		$er->name = 'foo bar';
		$er->slug = 'foobar';
		$er->save();
	}
}
