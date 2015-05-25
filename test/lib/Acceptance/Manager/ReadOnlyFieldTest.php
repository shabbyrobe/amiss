<?php
namespace Amiss\Test\Acceptance\Manager;

use Amiss\Test;

class ReadOnlyFieldTest extends \Amiss\Test\Helper\TestCase
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

    function testReadOnlyGetObject()
    {
        $t = $this->manager->getById('ArtistType', 1);
        $this->assertEquals('band', $t->getSlug());
    }

    function testReadOnlySave()
    {
        $t = $this->manager->getById('ArtistType', 1);
        $t->type = 'Hello Hello';
        $this->manager->save($t);

        $row = $this->manager->connector->query("SELECT * FROM artist_type WHERE artistTypeId=1")->fetch();
        $this->assertEquals('hello-hello', $row['slug']);        
    }
}
