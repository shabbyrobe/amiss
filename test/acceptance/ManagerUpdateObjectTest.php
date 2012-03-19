<?php

namespace Amiss\Test\Acceptance;

class ManagerUpdateObjectTest extends \NoteMapperDataTestCase
{
	public function setUp()
	{
		parent::setUp();
		
		$this->artist = $this->manager->get('Artist', 'artistId=?', 1);
		$this->assertEquals('Limozeen', $this->artist->name);
	}
	
	/**
	 * Ensures that only the EventArtist that we selected is updated. EventArtist
	 * has a multi-column primary.
	 * 
	 * @group acceptance
	 */
	public function testUpdateObjectByMultiKey()
	{
		$original = $this->manager->get('EventArtist', 'eventId=1 AND artistId=1');
		
		// make sure we have the right object
		$this->assertEquals(1, $original->artistId);
		$this->assertEquals(1, $original->eventId);
		$this->assertEquals(1, $original->priority);
		$this->assertEquals(1, $original->sequence);
		
		$original->sequence = 3000;
		
		$this->manager->update($original);
		
		$beforeEventArtists = $this->manager->get('EventArtist', 'eventId=1 AND artistId!=1');
		$afterEventArtists = $this->manager->get('EventArtist', 'eventId=1 AND artistId!=1');
		
		$this->assertEquals($beforeEventArtists, $afterEventArtists);
		
		$found = $this->manager->get('EventArtist', 'eventId=1 AND artistId=1');
		$this->assertEquals(3000, $found->sequence);
	}
	
	/**
	 * Ensures the signature for the 'autoincrement primary key' update method works
	 *   Amiss\Manager->update( object $object )
	 * 
	 */
	public function testUpdateObjectByAutoincrementPrimaryKey()
	{
		$this->artist->name = 'Foobar';
		
		$this->assertEquals(0, $this->manager->count('Artist', 'name="Foobar"'));
		
		$this->manager->update($this->artist);
		
		$this->artist = $this->manager->get('Artist', 'artistId=?', 1);
		$this->assertEquals('Foobar', $this->artist->name);
		
		$this->assertEquals(1, $this->manager->count('Artist', 'name="Foobar"'));
	}
	
	/**
	 * Ensures the following signature works as expected:
	 *   Amiss\Manager->update( object $object , string $positionalWhere , [ string $param1, ... ] )
	 * 
	 */
	public function testUpdateObjectWithPositionalWhereAndSingleParameter()
	{
		$this->artist->name = 'Foobar';
		
		$this->manager->update($this->artist, 'artistId=?', 1);
		
		$this->artist = $this->manager->get('Artist', 'artistId=?', 1);
		$this->assertEquals('Foobar', $this->artist->name);
		
		$this->assertEquals(1, $this->manager->count('Artist', 'name="Foobar"'));
	}
	
	/**
	 * Ensures the following signature works as expected:
	 *   Amiss\Manager->update( object $object , string $namedWhere , array $params )
	 * 
	 * @group acceptance
	 */
	public function testUpdateObjectWithNamedWhereAndSingleParameter()
	{
		$this->artist->name = 'Foobar';
		
		$this->manager->update($this->artist, 'artistId=:id', array('id'=>1));
		
		$this->artist = $this->manager->get('Artist', 'artistId=?', 1);
		$this->assertEquals('Foobar', $this->artist->name);
		
		$this->assertEquals(1, $this->manager->count('Artist', 'name="Foobar"'));
	}
	
	public function testUpdateUsingRowExporter()
	{
		$venue = $this->manager->get('Venue', 'venueId=?', 1);
		
		$this->assertEquals('Strong Badia', $venue->venueName);
		
		$venue->venueName = 'Pants Burg';
		$this->manager->update($venue, 'venueId');
		
		$venue = $this->manager->get('Venue', 'venueId=?', 1);
		$this->assertEquals('Pants Burg', $venue->venueName);
	}
}
