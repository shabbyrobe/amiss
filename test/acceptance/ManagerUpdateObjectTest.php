<?php
namespace Amiss\Test\Acceptance;

class ManagerUpdateObjectTest extends \ModelDataTestCase
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
     * @group manager
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
        
        $beforeEventArtists = $this->manager->getList('EventArtist', 'eventId=1 AND artistId!=1');
        $this->manager->update($original);
        $afterEventArtists = $this->manager->getList('EventArtist', 'eventId=1 AND artistId!=1');
        
        $this->assertEquals($beforeEventArtists, $afterEventArtists);
        
        // ensure all of the objects other than the one we are messing with are untouched
        $found = $this->manager->get('EventArtist', 'eventId=1 AND artistId=1');
        $this->assertEquals(3000, $found->sequence);
    }
    
    /**
     * Ensures the signature for the 'autoincrement primary key' update method works
     *   Amiss\Sql\Manager->update( object $object )
     *   
     * @group acceptance
     * @group manager
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
}
