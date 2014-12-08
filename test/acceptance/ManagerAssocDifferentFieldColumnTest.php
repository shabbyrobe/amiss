<?php
namespace Amiss\Test\Acceptance
{
    class ManagerAssocDifferentFieldColumnTest extends \ModelDataTestCase
    {
        public function setUp()
        {
            parent::setUp();
            $this->ns = 'Amiss\Demo\AssocDifferentFieldColumn';
            $tb = new \Amiss\Sql\TableBuilder($this->manager, $this->ns.'\Event');
            $tb->createTable();
            $tb = new \Amiss\Sql\TableBuilder($this->manager, $this->ns.'\Artist');
            $tb->createTable();
            $tb = new \Amiss\Sql\TableBuilder($this->manager, $this->ns.'\EventArtist');
            $tb->createTable();

            $manager = $this->manager;
            $manager->insert($this->ns.'\Event', ['eventId'=>1, 'name'=>'event1']);
            $manager->insert($this->ns.'\Event', ['eventId'=>2, 'name'=>'event2']);
            $manager->insert($this->ns.'\Event', ['eventId'=>3, 'name'=>'event3']);
            $manager->insert($this->ns.'\Artist', ['artistId'=>1, 'name'=>'artist1']);
            $manager->insert($this->ns.'\Artist', ['artistId'=>2, 'name'=>'artist2']);
            $manager->insert($this->ns.'\Artist', ['artistId'=>3, 'name'=>'artist3']);
            $manager->insert($this->ns.'\EventArtist', ['eventId'=>1, 'artistId'=>1]);
            $manager->insert($this->ns.'\EventArtist', ['eventId'=>1, 'artistId'=>2]);
            $manager->insert($this->ns.'\EventArtist', ['eventId'=>2, 'artistId'=>2]);
            $manager->insert($this->ns.'\EventArtist', ['eventId'=>2, 'artistId'=>3]);
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
     * @table adfc_artist
     */
    class Artist
    {
        /**
         * @primary
         * @field artistid
         */
        public $id;

        /** @field */
        public $name;

        /**
         * @has.assoc.of Amiss\Demo\AssocDifferentFieldColumn\Event
         * @has.assoc.via Amiss\Demo\AssocDifferentFieldColumn\EventArtist
         */
        public $events;
    }

    /**
     * @table adfc_event
     */
    class Event
    {
        /**
         * @primary
         * @field eventid
         */
        public $id;

        /** @field */
        public $name;

        /** 
         * @has.assoc.of Amiss\Demo\AssocDifferentFieldColumn\Artist
         * @has.assoc.via Amiss\Demo\AssocDifferentFieldColumn\EventArtist
         */
        public $artists;
    }

    /**
     * @table adfc_event_artist
     */
    class EventArtist
    {
        /** @primary */
        public $eventId;

        /**
         * @primary
         * @index
         */
        public $artistId;

        /**
         * @has.one.of Amiss\Demo\AssocDifferentFieldColumn\Event
         * @has.one.from primary
         */
        public $event;

        /**
         * @has.one.of Amiss\Demo\AssocDifferentFieldColumn\Artist
         * @has.one.from artistId
         */
        public $artist;
    }
}

