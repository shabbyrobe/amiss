<?php
namespace Amiss\Test\Acceptance
{
    use \Amiss\Sql\TableBuilder;

    /**
     * @group relator-assoc
     */
    class ManagerAssocDifferentFieldColumnTest extends \Amiss\Test\Helper\ModelDataTestCase
    {
        public function setUp()
        {
            parent::setUp();
            $this->ns = 'Amiss\Demo\AssocDifferentFieldColumn';
            $tb = TableBuilder::create($this->manager->connector, $this->manager->mapper, [
                $this->ns.'\Event',
                $this->ns.'\Artist',
                $this->ns.'\EventArtist',
            ]);

            $manager = $this->manager;
            $manager->insertTable($this->ns.'\Event', ['id'=>1, 'name'=>'event1']);
            $manager->insertTable($this->ns.'\Event', ['id'=>2, 'name'=>'event2']);
            $manager->insertTable($this->ns.'\Event', ['id'=>3, 'name'=>'event3']);
            $manager->insertTable($this->ns.'\Artist', ['id'=>1, 'name'=>'artist1']);
            $manager->insertTable($this->ns.'\Artist', ['id'=>2, 'name'=>'artist2']);
            $manager->insertTable($this->ns.'\Artist', ['id'=>3, 'name'=>'artist3']);
            $manager->insertTable($this->ns.'\EventArtist', ['eventId'=>1, 'artistId'=>1]);
            $manager->insertTable($this->ns.'\EventArtist', ['eventId'=>1, 'artistId'=>2]);
            $manager->insertTable($this->ns.'\EventArtist', ['eventId'=>2, 'artistId'=>2]);
            $manager->insertTable($this->ns.'\EventArtist', ['eventId'=>2, 'artistId'=>3]);
        }

        public function testAssignRelated()
        {
            $manager = $this->manager;
            $event = $manager->getById($this->ns.'\Event', 1);
            $manager->assignRelated($event, "artists");
            $this->assertCount(2, $event->artists);
            $this->assertEquals(1, $event->artists[0]->id);
            $this->assertEquals(2, $event->artists[1]->id);

            $artist = $manager->getById($this->ns.'\Artist', 2);
            $manager->assignRelated($artist, "events"); 
            $this->assertCount(2, $artist->events);
            $this->assertEquals(1, $artist->events[0]->id);
            $this->assertEquals(2, $artist->events[1]->id);
        }

        public function testAssignRelatedWhenNoRowsExist()
        {
            $manager = $this->manager;
            $event = $manager->getById($this->ns.'\Event', 3);
            $manager->assignRelated($event, "artists");
            $this->assertNull($event->artists);
        }
    }
}

namespace Amiss\Demo\AssocDifferentFieldColumn
{
    /**
     * :amiss = {"table": "adfc_artist"};
     */
    class Artist
    {
        /** :amiss = {"field": { "primary": true, "name": "artistId" }}; */
        public $id;

        /** :amiss = {"field": true}; */
        public $name;

        /**
         * :amiss = {"has": {
         *     "type": "assoc",
         *     "of"  : "Amiss\\Demo\\AssocDifferentFieldColumn\\Event",
         *     "via" : "Amiss\\Demo\\AssocDifferentFieldColumn\\EventArtist"
         * }};
         */
        public $events;
    }

    /**
     * :amiss = {"table": "adfc_event"};
     */
    class Event
    {
        /** :amiss = {"field": {"primary": true, "name": "eventId"}}; */
        public $id;

        /** :amiss = {"field": true}; */
        public $name;

        /**
         * :amiss = {"has": {
         *     "type": "assoc",
         *     "of"  : "Amiss\\Demo\\AssocDifferentFieldColumn\\Artist",
         *     "via" : "Amiss\\Demo\\AssocDifferentFieldColumn\\EventArtist"
         * }};
         */
        public $artists;
    }

    /**
     * :amiss = {"table": "adfc_event_artist"};
     */
    class EventArtist
    {
        /** :amiss = {"field": {"primary": true}}; */
        public $eventId;

        /** :amiss = {"field": {"primary": true, "index": true}}; */
        public $artistId;

        /**
         * :amiss = {"has": {
         *     "type": "one",
         *     "of"  : "Amiss\\Demo\\AssocDifferentFieldColumn\\Event",
         *     "from": "primary"
         * }};
         */
        public $event;

        /**
         * :amiss = {"has": {
         *     "type": "one",
         *     "of"  : "Amiss\\Demo\\AssocDifferentFieldColumn\\Artist",
         *     "from": "artistId"
         * }};
         */
        public $artist;
    }
}

