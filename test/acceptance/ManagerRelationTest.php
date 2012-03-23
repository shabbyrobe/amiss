<?php

namespace Amiss\Test\Acceptance;

use Amiss\Criteria\Query;

use Amiss\Demo;

class ManagerRelationTest extends \SqliteDataTestCase
{
	/**
	 * @group acceptance
	 * @group manager
	 */
	public function testGetRelatedSingle()
	{
		$eventArtist = $this->manager->get('EventArtist', 'eventId=? AND artistId=?', 2, 6);
		$event = $this->manager->getRelated($eventArtist, 'event');
		
		$this->assertTrue($event instanceof Demo\Event);
		$this->assertEquals('awexxome-fest-20x6', $event->getSlug());
	}
	
	/**
	 * @group acceptance
	 * @group manager
	 */
	public function testAssignRelatedSingle()
	{
		$eventArtist = $this->manager->get('EventArtist', 'eventId=? AND artistId=?', 2, 6);
		$this->manager->assignRelated($eventArtist, 'event');
		$this->assertTrue($eventArtist->event instanceof Demo\Event);
		$this->assertEquals('awexxome-fest-20x6', $eventArtist->event->getSlug());
	}
	
	/**
	 * @group acceptance
	 * @group manager
	 */
	public function testAssignRelatedSingleToList()
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
	
	/**
	 * @group acceptance
	 * @group manager
	 */
	public function testGetRelatedList()
	{
		$event = $this->manager->get('Event', 'eventId=1');
		$eventArtists = $this->manager->getRelated($event, 'eventArtists');
		
		$this->assertTrue(is_array($eventArtists));
		$this->assertTrue(count($eventArtists) > 1);
		$this->assertTrue($eventArtists[0] instanceof Demo\EventArtist);
		// TODO: improve checking
	}
	
	/**
	 * @group acceptance
	 * @group manager
	 */
	public function testGetRelatedAssocForSingle()
	{
		$event = $this->manager->get('Event', 'eventId=1');
		$artists = $this->manager->getRelated($event, 'artists');
		
		$this->assertTrue(is_array($artists));
		$this->assertGreaterThan(0, count($artists));
		
		$ids = array();
		foreach ($artists as $a) {
			$ids[] = $a->artistId;
		}
		$this->assertEquals(array(1, 2, 3, 4, 5, 7), $ids);
	}
	
	/**
	 * @group acceptance
	 * @group manager
	 */
	public function testGetRelatedAssocForList()
	{
		$events = $this->manager->getList('Event');
		$this->manager->assignRelated($events, 'artists');
		
		$ids = array();
		foreach ($events as $e) {
			$ids[$e->eventId] = array();
			foreach ($e->artists as $a) {
				$ids[$e->eventId][] = $a->artistId;
			}
		}
		
		$expected = array(
			1=>array(1, 2, 3, 4, 5, 7),
			2=>array(1, 2, 6),
		);
		
		$this->assertEquals($expected, $ids);
	}
	
	/**
	 * @group acceptance
	 * @group manager
	 */
	public function testGetRelatedAssocWithCriteria()
	{ 
		$event = $this->manager->get('Event', 'eventId=1');
		$criteria = new Query();
		$criteria->where = 'artistTypeId=:tid';
		$criteria->params = array('tid'=>1);
		
		$artists = $this->manager->getRelated($event, 'artists', $criteria);
		
		$ids = array();
		foreach ($artists as $a) {
			$ids[] = $a->artistId;
		}
		
		$this->assertEquals(array(1, 2, 3, 7), $ids);
	}
	
	/**
	 * @group acceptance
	 * @group manager
	 */
	public function testAssignRelatedList()
	{
		$event = $this->manager->get('Event', 'eventId=1');
		$this->manager->assignRelated($event, 'eventArtists');
		
		$this->assertTrue(is_array($event->eventArtists));
		$this->assertTrue(count($event->eventArtists) > 0);
		$this->assertEquals(1, $event->eventArtists[0]->artistId);
		$this->assertEquals(2, $event->eventArtists[1]->artistId);
	}
	
	/**
	 * @group acceptance
	 * @group manager
	 */
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
