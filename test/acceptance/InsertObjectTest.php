<?php

namespace Amiss\Test\Acceptance;

use Amiss\Demo;

class InsertObjectTest extends \SqliteDataTestCase
{
	/**
	 * Ensures the signature for object insertion works
	 *   Amiss\Manager->insert( object $object )
	 * 
	 */
	public function testInsertObject()
	{
		$this->assertEquals(0, $this->manager->count('Artist', 'slug="insert-test"'));
		
		$artist = new Demo\Artist();
		$artist->artistTypeId = 1;
		$artist->name = 'Insert Test';
		$artist->slug = 'insert-test';
		$artist->artistId = $this->manager->insert($artist);
		
		$this->assertGreaterThan(0, $artist->artistId);
		
		$this->assertEquals(1, $this->manager->count('Artist', 'slug="insert-test"'));
	}

	/**
	 * Ensures the signature for object insertion works with a RowExporter
	 *   Amiss\Manager->insert( object $object )
	 * 
	 */
	public function testInsertObjectUsingRowExporter()
	{
		$this->assertEquals(0, $this->manager->count('Venue', 'slug="insert-test"'));
		
		$venue = new Demo\Venue();
		$venue->venueName = 'Insert Test';
		$venue->venueSlug = 'insert-test';
		$venue->venueAddress = 'yep';
		$venue->venueShortAddress = 'yep';
		$venue->venueId = $this->manager->insert($venue);
		
		$this->assertGreaterThan(0, $venue->venueId);
		
		$this->assertEquals(1, $this->manager->count('Venue', 'slug="insert-test"'));
	}
	
	/**
	 * Ensures the signature for table insertion works
	 *   Amiss\Manager->insert( string $table , array $values )
	 * 
	 */
	public function testInsertToTable()
	{
		$this->assertEquals(0, $this->manager->count('Artist', 'slug="insert-table-test"'));
		
		$id = $this->manager->insert('Artist', array(
			'name'=>'Insert Table Test',
			'slug'=>'insert-table-test',
			'artistTypeId'=>1,
		));
		
		$this->assertGreaterThan(0, $id);
		
		$this->assertEquals(1, $this->manager->count('Artist', 'slug="insert-table-test"'));
	}
}
