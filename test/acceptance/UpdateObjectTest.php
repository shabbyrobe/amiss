<?php

namespace Amiss\Test\Acceptance;

class UpdateObjectTest extends \NoteMapperDataTestCase
{
	public function setUp()
	{
		parent::setUp();
		
		$this->artist = $this->manager->get('Artist', 'artistId=?', 1);
		$this->assertEquals('Limozeen', $this->artist->name);
	}
	
	/**
	 * Ensures the signature for the 'autoincrement primary key' update method works
	 *   Amiss\Manager->update( object $object , string $pkField )
	 * 
	 */
	public function testUpdateObjectByAutoincrementPrimaryKey()
	{
		$this->artist->name = 'Foobar';
		
		$this->assertEquals(0, $this->manager->count('Artist', 'name="Foobar"'));
		
		$this->manager->update($this->artist, 'artistId');
		
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
