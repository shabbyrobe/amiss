<?php
namespace Amiss\Test\Acceptance\Manager;

class ReadOnlyFieldTest extends \ModelDataTestCase
{
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
