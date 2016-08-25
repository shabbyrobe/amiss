<?php
namespace Amiss\Test\Acceptance;

use Amiss\Demo;
use Amiss\Test;

/**
 * @group acceptance
 * @group manager
 */
class ManagerUpdateObjectTest extends \Amiss\Test\Helper\TestCase
{
    public function setUp()
    {
        $this->deps = Test\Factory::managerModelDemo();
        $this->manager = $this->deps->manager;
        $this->artist = $this->manager->get(Demo\Artist::class, 'artistId=?', array(1));
        $this->assertEquals('Limozeen', $this->artist->name);
    }

    public function tearDown()
    {
        $this->manager = null;
        $this->deps = null;
        parent::tearDown();
    }

    /**
     * Ensures that only the EventArtist that we selected is updated. EventArtist
     * has a multi-column primary.
     */
    public function testUpdateObjectByMultiKey()
    {
        $original = $this->manager->get(Demo\EventArtist::class, 'eventId=1 AND artistId=1');
        
        // make sure we have the right object
        $this->assertEquals(1, $original->artistId);
        $this->assertEquals(1, $original->eventId);
        $this->assertEquals(1, $original->priority);
        $this->assertEquals(1, $original->sequence);
        
        $original->sequence = 3000;
        
        $beforeEventArtists = $this->manager->getList(Demo\EventArtist::class, 'eventId=1 AND artistId!=1');
        $this->manager->update($original);
        $afterEventArtists = $this->manager->getList(Demo\EventArtist::class, 'eventId=1 AND artistId!=1');
        
        $this->assertEquals($beforeEventArtists, $afterEventArtists);
        
        // ensure all of the objects other than the one we are messing with are untouched
        $found = $this->manager->get(Demo\EventArtist::class, 'eventId=1 AND artistId=1');
        $this->assertEquals(3000, $found->sequence);
    }
    
    /**
     * Ensures the signature for the 'autoincrement primary key' update method works
     *   Amiss\Sql\Manager->update( object $object )
     */
    public function testUpdateObjectByAutoincrementPrimaryKey()
    {
        $this->artist->name = 'Foobar';
        
        $this->assertEquals(0, $this->manager->count(Demo\Artist::class, 'name="Foobar"'));
        
        $this->manager->update($this->artist);
        
        $this->artist = $this->manager->get(Demo\Artist::class, 'artistId=?', array(1));
        $this->assertEquals('Foobar', $this->artist->name);
        
        $this->assertEquals(1, $this->manager->count(Demo\Artist::class, 'name="Foobar"'));
    }
}
