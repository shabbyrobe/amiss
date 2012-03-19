<?php

namespace Amiss\Test\Acceptance;

use Amiss\Demo;

class RelationTest extends \NoteMapperDataTestCase
{
	public function testRetrieveSingleRelated()
	{
		$eventArtist = $this->manager->get('EventArtist', 'eventId=? AND artistId=?', 2, 6);
		$event = $this->manager->getRelated($eventArtist, 'event');
		
		$this->assertTrue($event instanceof Demo\Event);
		$this->assertEquals('awexxome-fest-20x6', $event->getSlug());
	}
	
	public function testAssignSingleRelated()
	{
		$eventArtist = $this->manager->get('EventArtist', 'eventId=? AND artistId=?', 2, 6);
		$this->manager->assignRelated($eventArtist, 'event');
		$this->assertTrue($eventArtist->event instanceof Demo\Event);
		$this->assertEquals('awexxome-fest-20x6', $eventArtist->event->getSlug());
	}
	
	/*
	This is actually not supported - it's just a bit too hacked up to try to tell the
	difference between an array of models passed as the source, and array($source, $intoProperty). 
	public function testRetrieveSingleRelatedForList()
	{
		$eventArtists = $this->manager->getList('EventArtist', 'eventId=?', 1);
		$events = $this->manager->getRelated($eventArtists, 'Event', 'eventId');
		$this->assertTrue(is_array($events));
		$this->assertTrue(current($events) instanceof Demo\Event);
		$this->assertEquals('awexxome-fest', current($events)->getSlug());
	}
	*/
	
	public function testAssignSingleRelatedToList()
	{
		$eventArtist = $this->manager->getList('EventArtist', 'eventId=?', 1);
		$this->manager->assignRelated($eventArtist, 'event');
		
		$current = current($eventArtist);
		$this->assertTrue($current->event instanceof Demo\Event);
		$this->assertEquals('awexxome-fest', $current->event->getSlug());
		
		// make sure the second object has exactly the same instance
		next($eventArtist);
		$next = current($eventArtist);
		$this->assertTrue($current->event === $next->event);
	}
	
	public function testRetrieveRelatedList()
	{
		$event = $this->manager->get('Event', 'eventId=1');
		$eventArtists = $this->manager->getRelated($event, 'eventArtists');
		
		$this->assertTrue(is_array($eventArtists));
		$this->assertTrue(count($eventArtists) > 0);
		
		// TODO: improve checking
	}
	
	public function testAssignRelatedList()
	{
		$event = $this->manager->get('Event', 'eventId=1');
		$this->manager->assignRelated($event, 'eventArtists');
		
		$this->assertTrue(is_array($event->eventArtists));
		$this->assertTrue(count($event->eventArtists) > 0);
		$this->assertEquals(1, $event->eventArtists[0]->artistId);
		$this->assertEquals(2, $event->eventArtists[1]->artistId);
	}
	
	public function testAssignRelatedListToList()
	{
		$types = $this->manager->getList('ArtistType');
		
		$this->assertTrue(is_array($types));
		$this->assertTrue(current($types) instanceof Demo\ArtistType);
		$this->assertEquals(array(), current($types)->artists);
		
		$this->manager->assignRelated($types, 'artists');
		$this->assertTrue(current(current($types)->artists) instanceof Demo\Artist);
		next(current($types)->artists);
		$this->assertTrue(current(current($types)->artists) instanceof Demo\Artist);
	}
}
