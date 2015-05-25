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
        $this->assertEquals(0, $this->manager->count('Artist', 'slug="insert-test"'));
        
        $artist = new Demo\Artist();
        $artist->artistTypeId = 1;
        $artist->name = 'Insert Test';
        $artist->slug = 'insert-test';
        $this->manager->save($artist);
        
        $this->assertGreaterThan(0, $artist->artistId);
        
        $this->assertEquals(1, $this->manager->count('Artist', 'slug="insert-test"'));
    }

    function testUpdateObjectWithSave()
    {
        $original = $this->manager->get('Artist', 'artistId=1');

        // make sure we have the right object
        $this->assertEquals(1, $original->artistId);
        $this->assertEquals(1, $original->artistTypeId);
        $this->assertEquals("Limozeen", $original->name);
        
        $original->name = "Yep yep yep";
        
        $beforeArtists = $this->manager->getList('Artist', 'artistId!=1');
        $this->manager->save($original);
        $afterArtists = $this->manager->getList('Artist', 'artistId!=1');
        
        // ensure all of the objects other than the one we are messing with are untouched
        $this->assertEquals($beforeArtists, $afterArtists);
        
        $found = $this->manager->get('Artist', 'artistId=1');
        $this->assertEquals("Yep yep yep", $found->name);
    }

    function testUpdateRowCount()
    {
        // there are 3 artist types in the test data
        // with MySQL, only changed ones are counted but with Sqlite, all
        // rows matched by the clause are counted
        $expected = $this->deps->connector->engine == 'sqlite' ? 3 : 2;
        $this->assertEquals($expected, $this->manager->updateTable('ArtistType', ['type'=>'Band'], '1=1'));
    }

    public function testSaveFailsWhenAutoincNotDeclared()
    {
        $object = new Demo\EventArtist();
        $this->setExpectedException(
            'Amiss\Exception', 
            "Manager requires a single-column autoincrement primary if you want to call 'save'"
        );
        $this->manager->save($object);
    }
}
