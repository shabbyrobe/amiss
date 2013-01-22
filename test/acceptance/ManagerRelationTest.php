<?php
namespace Amiss\Test\Acceptance;

use Amiss\Sql\Criteria\Query;

use Amiss\Demo;

/**
 * @group acceptance
 * @group manager
 */
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
    
    public function testAssignRelatedSingle()
    {
        $eventArtist = $this->manager->get('EventArtist', 'eventId=? AND artistId=?', 2, 6);
        $this->manager->assignRelated($eventArtist, 'event');
        $this->assertTrue($eventArtist->event instanceof Demo\Event);
        $this->assertEquals('awexxome-fest-20x6', $eventArtist->event->getSlug());
    }
    
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
    
    public function testGetRelatedList()
    {
        $event = $this->manager->get('Event', 'eventId=1');
        $eventArtists = $this->manager->getRelated($event, 'eventArtists');
        
        $this->assertTrue(is_array($eventArtists));
        $this->assertTrue(count($eventArtists) > 1);
        $this->assertTrue($eventArtists[0] instanceof Demo\EventArtist);
        // TODO: improve checking
    }
    
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
    
    public function testAssignRelatedDeepThroughAssocToSingle()
    {
        $event = $this->manager->getById('Event', 1);
        
        // Relation 1: populate each Event object's list of artists through EventArtists
        $this->manager->assignRelated($event, 'artists');
        
        // Relation 2: populate each Artist object's artistType property
        $this->manager->assignRelated($this->manager->getChildren($event, 'artists'), 'artistType');
        
        $this->assertInstanceOf('Amiss\Demo\Event', $event);
        $this->assertGreaterThan(0, count($event->artists));
        $this->assertInstanceOf('Amiss\Demo\Artist', $event->artists[0]);
        $this->assertInstanceOf('Amiss\Demo\ArtistType', $event->artists[0]->artistType);
    }
}
