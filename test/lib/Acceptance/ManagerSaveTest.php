<?php
namespace Amiss\Test\Acceptance;

use Amiss\Demo;
use Amiss\Test;

/**
 * @group acceptance
 * @group manager
 */
class ManagerSaveTest extends \Amiss\Test\Helper\TestCase
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
     *   Amiss\Manager->save( object $object )
     */
    public function testSaveNewObject()
    {
        $this->assertEquals(0, $this->manager->count(Demo\Artist::class, 'slug="insert-test"'));
        
        $artist = new Demo\Artist();
        $artist->artistTypeId = 1;
        $artist->name = 'Insert Test';
        $artist->slug = 'insert-test';
        $this->manager->save($artist);
        
        $this->assertGreaterThan(0, $artist->artistId);
        
        $this->assertEquals(1, $this->manager->count(Demo\Artist::class, 'slug="insert-test"'));
    }

    function testUpdateObjectWithSave()
    {
        $original = $this->manager->get(Demo\Artist::class, 'artistId=1');

        // make sure we have the right object
        $this->assertEquals(1, $original->artistId);
        $this->assertEquals(1, $original->artistTypeId);
        $this->assertEquals("Limozeen", $original->name);
        
        $original->name = "Yep yep yep";
        
        $beforeArtists = $this->manager->getList(Demo\Artist::class, 'artistId!=1');
        $this->manager->save($original);
        $afterArtists = $this->manager->getList(Demo\Artist::class, 'artistId!=1');
        
        // ensure all of the objects other than the one we are messing with are untouched
        $this->assertEquals($beforeArtists, $afterArtists);
        
        $found = $this->manager->get(Demo\Artist::class, 'artistId=1');
        $this->assertEquals("Yep yep yep", $found->name);
    }

    function testUpdateRowCount()
    {
        // there are 3 artist types in the test data
        // with MySQL, only changed ones are counted but with Sqlite, all
        // rows matched by the clause are counted
        $expected = $this->deps->connector->engine == 'sqlite' ? 3 : 2;
        $this->assertEquals($expected, $this->manager->updateTable(Demo\ArtistType::class, ['type'=>'Band'], '1=1'));
    }

    public function testSaveNoAutoincUpdates()
    {
        $object = new Demo\EventArtist();
        $object->artistId = 1;
        $object->eventId = 1;
        $object->priority = 1;
        $object->sequence = 1;

        $this->manager->connector->exec("DELETE FROM event_artist");
        $this->manager->insert($object);

        $object->sequence = 2;
        $this->manager->save($object);

        $rows = $this->manager->connector->query("SELECT * FROM event_artist")->fetchAll(\PDO::FETCH_ASSOC);
        $this->assertCount(1, $rows);
        $this->assertEquals(2, $rows[0]['sequence']);
    }

    public function testSaveNoAutoincBeforeSavePopulatedPrimaryInserts()
    {
        $this->manager->connector->exec("DELETE FROM event_artist");

        $object = new Demo\EventArtist();
        $object->priority = 1;
        $object->sequence = 1;
        $meta = $this->manager->mapper->getMeta(Demo\EventArtist::class);
        $this->manager->on['beforeSave'] = [function($object) {
            $object->artistId = 1;
            $object->eventId  = 1;
        }];

        $this->manager->save($object, $meta);

        $sql  = "SELECT priority, sequence, artistId, eventId FROM event_artist";
        $rows = $this->manager->connector->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        $expected = [[
            'priority' => '1',
            'sequence' => '1',
            'artistId' => '1',
            'eventId'  => '1',
        ]];
        $this->assertEquals($expected, $rows);
    }
}
