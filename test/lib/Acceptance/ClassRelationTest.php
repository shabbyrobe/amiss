<?php
namespace Amiss\Test\Acceptance
{
    use Amiss\Sql\TableBuilder;

    class ClassRelationTest extends \Amiss\Test\Helper\TestCase
    {
        private $manager;

        public function setUp()
        {
            $this->db = new \PDOK\Connector('sqlite::memory:');
            $this->mapper = $this->createDefaultMapper();
            $this->manager = new \Amiss\Sql\Manager($this->db, $this->mapper);
            $this->manager->relators = \Amiss\Sql\Factory::createRelators();

            foreach (['Artist', 'ArtistType'] as $c) {
                TableBuilder::create($this->manager->connector, $this->mapper, __CLASS__.'\\'.$c);
            }

            $this->manager->connector->exec("INSERT INTO artist (artistId, typeId) VALUES(1, 1)");
            $this->manager->connector->exec("INSERT INTO artist (artistId, typeId) VALUES(2, 1)");
            $this->manager->connector->exec("INSERT INTO artist_type (typeId) VALUES(1)");
        }

        public function createDefaultMapper()
        {
            $mapper = new \Amiss\Mapper\Note();
            $mapper->objectNamespace = __CLASS__;
            return $mapper;
        }

        function testHasOneGetRelated()
        {
            $artist = $this->manager->getById('Artist', 1);
            $type = $this->manager->getRelated($artist, 'type');
            $this->assertInstanceOf(ClassRelationTest\ArtistType::class, $type);
        }

        function testHasOneAssignRelatedFails()
        {
            $artist = $this->manager->getById('Artist', 1);
            $this->setExpectedException(\Amiss\Exception::class, 'Relation type is not assignable');
            $this->manager->assignRelated($artist, 'type');
        }

        function testHasManyGetRelated()
        {
            $type = $this->manager->getById('ArtistType', 1);
            $artists = $this->manager->getRelated($type, 'artists');
            $this->assertInternalType('array', $artists);
            $this->assertCount(1, $artists);
            $this->assertInstanceOf(ClassRelationTest\Artist::class, $artists[0]);
        }

        function testHasManyAssignRelatedFails()
        {
            $type = $this->manager->getById('ArtistType', 1);
            $this->setExpectedException(\Amiss\Exception::class, 'Relation artists is not assignable');
            $this->manager->assignRelated($type, 'artists');
        }
    }
}

namespace Amiss\Test\Acceptance\ClassRelationTest
{
    /**
     * :amiss = {
     *     "relations": {
     *         "type": {
     *             "type": "one",
     *             "of"  : "ArtistType",
     *             "from": "typeId"
     *         }
     *     },
     *     "indexes": {
     *         "typeId": true
     *     }
     * };
     */
    class Artist {
        /** :amiss = {"field": {"primary": true}}; */
        public $artistId;

        /** :amiss = {"field": true}; */
        public $typeId;
    }

    /**
     * :amiss = {"relations": {
     *     "artists": {"type": "many", "of": "Artist"}
     * }};
     */
    class ArtistType {
        /** :amiss = {"field": {"primary": true}}; */
        public $typeId;
    }
}
