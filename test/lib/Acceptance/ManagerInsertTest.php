<?php
namespace Amiss\Test\Acceptance;

use Amiss\Demo;
use Amiss\Test;

/**
 * @group acceptance
 * @group manager
 */
class ManagerInsertTest extends \Amiss\Test\Helper\TestCase
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

    /**
     * Ensures the signature for object insertion works
     *   Amiss\Sql\Manager->insert( object $object )
     */
    public function testInsertObject()
    {
        $this->assertEquals(0, $this->manager->count('Artist', 'slug="insert-test"'));
            
        $artist = new Demo\Artist();
        $artist->artistTypeId = 1;
        $artist->name = 'Insert Test';
        $artist->slug = 'insert-test';
        $this->manager->insert($artist);
        
        $this->assertGreaterThan(0, $artist->artistId);
        
        $this->assertEquals(1, $this->manager->count('Artist', 'slug="insert-test"'));
    }
    
    /**
     * Ensures object insertion works with a complex mapping (Venue
     * defines explicit field mappings)
     */
    public function testInsertObjectWithManualNoteFields()
    {
        $this->assertEquals(0, $this->manager->count('Venue', 'slug="insert-test"'));
        
        $venue = new Demo\Venue();
        $venue->venueName = 'Insert Test';
        $venue->venueSlug = 'insert-test';
        $venue->venueAddress = 'yep';
        $venue->venueShortAddress = 'yep';
        $this->manager->insert($venue);
        
        $this->assertGreaterThan(0, $venue->venueId);
        
        $row = $this->manager->connector->prepare("SELECT * from venue WHERE venueId=?")
            ->execute(array($venue->venueId))
            ->fetch(\PDO::FETCH_ASSOC);

        $this->assertEquals($venue->venueName, $row['name']);
        $this->assertEquals($venue->venueSlug, $row['slug']);
        $this->assertEquals($venue->venueAddress, $row['address']);
        $this->assertEquals($venue->venueShortAddress, $row['shortAddress']);
    }
    
    /**
     * Ensures the signature for table insertion works
     *   Amiss\Sql\Manager->insert( string $table , array $values )
     * 
     * @group acceptance
     * @group manager
     */
    public function testInsertTable()
    {
        $this->assertEquals(0, $this->manager->count('Artist', 'slug="insert-table-test"'));
        
        $id = $this->manager->insertTable('Artist', array(
            'name'=>'Insert Table Test',
            'slug'=>'insert-table-test',
            'artistTypeId'=>1,
        ));
        
        $this->assertGreaterThan(0, $id);
        
        $this->assertEquals(1, $this->manager->count('Artist', 'slug="insert-table-test"'));
    }
}
