<?php

namespace Amiss\Test\Acceptance;

class UpdateObjectMultiKeyTest extends \SqliteDataTestCase
{
	public function setUp()
	{
		parent::setUp();
		
		$this->eventArtist = $this->manager->get('EventArtist', 'eventId=1 AND artistId=1');
		
		// make sure we have the right object
		$this->assertEquals(1, $this->eventArtist->artistId);
		$this->assertEquals(1, $this->eventArtist->eventId);
		$this->assertEquals(1, $this->eventArtist->priority);
		$this->assertEquals(1, $this->eventArtist->sequence);
		
		$this->eventArtist->priority = 2000;
	}
	
	/**
	 * Ensures the following signature works as expected:
	 *   Amiss\Manager->update( object $object , array $pkKeyValues )
	 * 
	 */
	public function testUpdateObjectByMultiKey()
	{
		$this->eventArtist->sequence = 3000;
		
		$this->manager->update($this->eventArtist, array('eventId'=>1, 'artistId'=>1));
		
		$this->eventArtist = $this->manager->get('EventArtist', 'eventId=1 AND artistId=1');
		
		$this->assertEquals(1, $this->eventArtist->artistId);
		$this->assertEquals(1, $this->eventArtist->eventId);
		$this->assertEquals(2000, $this->eventArtist->priority);
		$this->assertEquals(3000, $this->eventArtist->sequence);
		
		$this->assertEquals(2, $this->manager->count('EventArtist', 'priority=2000 AND sequence=3000'));
	}
	
	/**
	 * Ensures the following signature works as expected:
	 *   Amiss\Manager->update( object $object , string $positionalWhere , [ string $param1, ... ] )
	 * 
	 */
	public function testUpdateObjectWithPositionalWhereAndMultipleParameters()
	{
		$this->eventArtist->priority = 2000;
		$this->eventArtist->sequence = 3000;
		
		$this->manager->update($this->eventArtist, 'eventId=? AND artistId=?', 1, 1);
		
		$this->eventArtist = $this->manager->get('EventArtist', 'eventId=1 AND artistId=1');
		
		$this->assertEquals(1, $this->eventArtist->artistId);
		$this->assertEquals(1, $this->eventArtist->eventId);
		$this->assertEquals(2000, $this->eventArtist->priority);
		$this->assertEquals(3000, $this->eventArtist->sequence);
		
		$this->assertEquals(2, $this->manager->count('EventArtist', 'priority=2000 AND sequence=3000'));
	}
	
	/**
	 * Ensures the following signature works as expected:
	 *   Amiss\Manager->update( object $object , string $namedWhere , array $params )
	 * 
	 */
	public function testUpdateObjectWithNamedWhereAndMultipleParameters()
	{
		$this->eventArtist->priority = 2000;
		$this->eventArtist->sequence = 3000;
		
		$this->manager->update($this->eventArtist, 'eventId=:e AND artistId=:a', array(':e'=>1, ':a'=>1));
		
		$this->eventArtist = $this->manager->get('EventArtist', 'eventId=1 AND artistId=1');
		
		$this->assertEquals(1, $this->eventArtist->artistId);
		$this->assertEquals(1, $this->eventArtist->eventId);
		$this->assertEquals(2000, $this->eventArtist->priority);
		$this->assertEquals(3000, $this->eventArtist->sequence);
		
		$this->assertEquals(2, $this->manager->count('EventArtist', 'priority=2000 AND sequence=3000'));
	}
}
