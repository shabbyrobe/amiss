<?php
namespace Amiss\Test\Acceptance
{
    use Amiss\Sql\TableBuilder;
    use Amiss\Demo;

    class ClassRelationTest extends \Amiss\Test\Helper\TestCase
    {
        private $manager;

        public function setUp()
        {
            $this->db = new \PDOK\Connector('sqlite::memory:');
            $this->mapper = $this->createDefaultMapper();
            $this->manager = new \Amiss\Sql\Manager($this->db, $this->mapper);
            $this->manager->relators = \Amiss\Sql\Factory::createRelators();

            foreach ([Demo\Artist::class, Demo\ArtistType::class] as $c) {
                TableBuilder::create($this->manager->connector, $this->mapper, $c);
            }

            $this->manager->connector->exec("INSERT INTO artist (artistId, artistTypeId) VALUES(1, 1)");
            $this->manager->connector->exec("INSERT INTO artist (artistId, artistTypeId) VALUES(2, 1)");
            $this->manager->connector->exec("INSERT INTO artist_type (artistTypeId) VALUES(1)");
        }

        public function createDefaultMapper()
        {
            $mapper = new \Amiss\Mapper\Note();
            return $mapper;
        }

        function testHasOneGetRelated()
        {
            $artist = $this->manager->getById(ClassRelationTest\Artist::class, 1);
            $type = $this->manager->getRelated($artist, 'artistType');
            $this->assertInstanceOf(ClassRelationTest\ArtistType::class, $type);
        }

        function testHasOneAssignRelatedFails()
        {
            $artist = $this->manager->getById(ClassRelationTest\Artist::class, 1);
            $this->setExpectedException(\Amiss\Exception::class, 'Relation artistType is not assignable');
            $this->manager->assignRelated($artist, 'artistType');
        }

        function testHasManyGetRelated()
        {
            $type = $this->manager->getById(ClassRelationTest\ArtistType::class, 1);
            $artists = $this->manager->getRelated($type, 'artists');
            $this->assertInternalType('array', $artists);
            $this->assertCount(1, $artists);
            $this->assertInstanceOf(ClassRelationTest\Artist::class, $artists[0]);
        }

        function testHasManyAssignRelatedFails()
        {
            $type = $this->manager->getById(ClassRelationTest\ArtistType::class, 1);
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
     *         "artistType": {
     *             "type": "one",
     *             "of"  : "Amiss\\Test\\Acceptance\\ClassRelationTest\\ArtistType",
     *             "from": "artistTypeId"
     *         }
     *     },
     *     "indexes": {
     *         "artistTypeId": true
     *     }
     * };
     */
    class Artist {
        /** :amiss = {"field": {"primary": true}}; */
        public $artistId;

        /** :amiss = {"field": true}; */
        public $artistTypeId;
    }

    /**
     * :amiss = {"relations": {
     *     "artists": {"type": "many", "of": "Amiss\\Test\\Acceptance\\ClassRelationTest\\Artist"}
     * }};
     */
    class ArtistType {
        /** :amiss = {"field": {"primary": true}}; */
        public $artistTypeId;
    }
}
