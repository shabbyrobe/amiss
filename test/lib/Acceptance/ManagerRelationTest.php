<?php
namespace Amiss\Test\Acceptance;

use Amiss\Sql\Query\Criteria;
use Amiss\Demo;
use Amiss\Functions;
use Amiss\Test;

/**
 * @group acceptance
 * @group manager
 */
class ManagerRelationTest extends \Amiss\Test\Helper\TestCase
{
    public function setUp()
    {
        $this->deps = Test\Factory::managerModelDemo();
        $this->manager = $this->deps->manager;
    }

    public function tearDown()
    {
        $this->manager = null;
        $this->deps = null;
        parent::tearDown();
    }

    public function testGetRelatedSingle()
    {
        $eventArtist = $this->manager->get(Demo\EventArtist::class, 'eventId=? AND artistId=?', [2, 6]);
        $event = $this->manager->getRelated($eventArtist, 'event');
        
        $this->assertTrue($event instanceof Demo\Event);
        $this->assertEquals('awexxome-fest-20x6', $event->getSlug());
    }
    
    public function testAssignSingleSourceToOneRelation()
    {
        $eventArtist = $this->manager->get(Demo\EventArtist::class, 'eventId=? AND artistId=?', [2, 6]);
        $this->manager->assignRelated($eventArtist, 'event');
        $this->assertTrue($eventArtist->event instanceof Demo\Event);
        $this->assertEquals('awexxome-fest-20x6', $eventArtist->event->getSlug());
    }

    public function testAssignSingleSourceToMultipleOneRelations()
    {
        $eventArtist = $this->manager->get(Demo\EventArtist::class, 'eventId=? AND artistId=?', [2, 6]);
        $this->manager->assignRelated($eventArtist, ['event', 'artist']);
        $this->assertTrue($eventArtist->event instanceof Demo\Event);
        $this->assertTrue($eventArtist->artist instanceof Demo\Artist);
        $this->assertEquals('awexxome-fest-20x6', $eventArtist->event->getSlug());
        $this->assertEquals('the-sonic-manipulator', $eventArtist->artist->slug);
        $this->assertEquals(3, $this->deps->connector->queries);
    }
 
    public function testAssignListSourceToMultipleOneRelations()
    {
        $eventArtist = $this->manager->getList(Demo\EventArtist::class, 'eventId=?', [1]);
        $this->manager->assignRelated($eventArtist, ['event', 'artist']);
        
        $current = current($eventArtist);
        $this->assertTrue($current->event instanceof Demo\Event);
        $this->assertTrue($current->artist instanceof Demo\Artist);
    }

    public function testGetRelatedList()
    {
        $event = $this->manager->get(Demo\Event::class, 'eventId=1');
        $eventArtists = $this->manager->getRelated($event, 'eventArtists');
        
        $this->assertTrue(is_array($eventArtists));
        $this->assertTrue(count($eventArtists) > 1);
        $this->assertTrue($eventArtists[0] instanceof Demo\EventArtist);
        // TODO: improve checking
    }
 
    /**
     * @group relator-assoc
     */
    public function testGetRelatedAssocForSingle()
    {
        $event = $this->manager->get(Demo\Event::class, 'eventId=1');
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
     * @group relator-assoc
     */
    public function testGetRelatedAssocForList()
    {
        $events = $this->manager->getList(Demo\Event::class);
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
     * @group relator-assoc
     */
    public function testGetRelatedAssocWithCriteria()
    { 
        $event = $this->manager->get(Demo\Event::class, 'eventId=1');
        $criteria = new Criteria();
        $criteria->where = 'artistTypeId=:tid';
        $criteria->params = array('tid'=>1);

        $artists = $this->manager->getRelated($event, 'artists', $criteria);

        $ids = array();
        foreach ($artists as $a) {
            $ids[] = $a->artistId;
        }
        
        $this->assertEquals(array(1, 2, 3, 7), $ids);
    }
    
    public function testAssignSingleSourceToManyRelation()
    {
        $event = $this->manager->get(Demo\Event::class, 'eventId=1');
        $this->manager->assignRelated($event, 'eventArtists');
        
        $this->assertTrue(is_array($event->eventArtists));
        $this->assertTrue(count($event->eventArtists) > 0);
        $this->assertEquals(1, $event->eventArtists[0]->artistId);
        $this->assertEquals(2, $event->eventArtists[1]->artistId);
    }
    
    public function testAssignSingleSourceToMultipleManyRelations()
    {
        $event = $this->manager->get(Demo\Event::class, 'eventId=1');
        $this->manager->assignRelated($event, ['eventArtists', 'tickets']);
        
        $this->assertTrue($event->eventArtists[0] instanceof Demo\EventArtist);
        $this->assertTrue($event->tickets[0] instanceof Demo\Ticket);
    }
    
    public function testAssignListSourceToManyRelation()
    {
        $types = $this->manager->getList(Demo\ArtistType::class);
        
        $this->assertTrue(is_array($types));
        $this->assertTrue(current($types) instanceof Demo\ArtistType);
        $this->assertEquals(array(), current($types)->artists);
        
        $this->manager->assignRelated($types, 'artists');
        $this->assertTrue(current(current($types)->artists) instanceof Demo\Artist);
        next(current($types)->artists);
        $this->assertTrue(current(current($types)->artists) instanceof Demo\Artist);
    }
    
    public function testAssignListSourceToMultipleManyRelations()
    {
        $events = $this->manager->getList(Demo\Event::class);
        $this->assertCount(2, $events);
        $this->manager->assignRelated($events, ['eventArtists', 'tickets']);
        
        foreach ($events as $event) {
            $this->assertTrue($event->eventArtists[0] instanceof Demo\EventArtist);
            $this->assertTrue($event->tickets[0] instanceof Demo\Ticket);
        }
    }

    /**
     * @group relator-assoc
     */
    public function testAssignRelatedDeepThroughAssocToSingle()
    {
        $event = $this->manager->getById(Demo\Event::class, 1);
        
        // Relation 1: populate each Event object's list of artists through EventArtists
        $this->manager->assignRelated($event, 'artists');
        
        // Relation 2: populate each Artist object's artistType property
        $this->manager->assignRelated($this->manager->getChildren($event, 'artists'), 'artistType');
        
        $this->assertInstanceOf(Demo\Event::class, $event);
        $this->assertGreaterThan(0, count($event->artists));
        $this->assertInstanceOf(Demo\Artist::class, $event->artists[0]);
        $this->assertInstanceOf(Demo\ArtistType::class, $event->artists[0]->artistType);
    }

    public function testGetAssignsToOneRelationUsingCriteria()
    {
        $query = [
            'params'=>[2, 6],
            'where'=>'eventId=? AND artistId=?',
            'with'=>'event',
        ];
        $eventArtist = $this->manager->get(Demo\EventArtist::class, $query);
        $this->assertTrue($eventArtist->event instanceof Demo\Event);
    }
    
    public function testGetAssignsToMultipleOneRelationsUsingCriteria()
    {
        $query = [
            'params'=>[2, 6],
            'where'=>'eventId=? AND artistId=?',
            'with'=>['event', 'artist'],
        ];
        $eventArtist = $this->manager->get(Demo\EventArtist::class, $query);
        $this->assertTrue($eventArtist->event instanceof Demo\Event);
        $this->assertTrue($eventArtist->artist instanceof Demo\Artist);
    }

    public function testGetListAssignsToOneRelationUsingCriteria()
    {
        $query = [
            'params'=>[1],
            'where'=>'eventId=?',
            'with'=>'event',
        ];
        $eventArtist = $this->manager->getList(Demo\EventArtist::class, $query);
        $current = current($eventArtist);
        $this->assertTrue($current->event instanceof Demo\Event);
        $this->assertEquals('awexxome-fest', $current->event->getSlug());
    }
}
