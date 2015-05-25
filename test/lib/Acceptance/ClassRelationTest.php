<?php
namespace Amiss\Test\Acceptance;

use Amiss\Sql\TableBuilder;

class ClassRelationTest extends \Amiss\Test\Helper\TestCase
{
    private $manager;

    public static function setUpBeforeClass()
    {
        self::createClassScopeClass('Artist', '
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
        ');

        self::createClassScopeClass('ArtistType', '
            /**
             * :amiss = {"relations": {
             *     "artists": {"type": "many", "of": "Artist"}
             * }};
             */
            class ArtistType {
                /** :amiss = {"field": {"primary": true}}; */
                public $typeId;
            }
        ');
    }

    public function setUp()
    {
        $this->db = new \PDOK\Connector('sqlite::memory:');
        $this->mapper = $this->createDefaultMapper();
        $this->manager = new \Amiss\Sql\Manager($this->db, $this->mapper);
        $this->manager->relators = \Amiss\Sql\Factory::createRelators();

        foreach (self::$classScopeClasses as $fqcn=>$_) {
            TableBuilder::create($this->manager->connector, $this->mapper, $fqcn);
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
        $this->assertInstanceOf($this->classScopeClassName('ArtistType'), $type);
    }

    function testHasOneAssignRelatedFails()
    {
        $artist = $this->manager->getById('Artist', 1);
        $this->setExpectedException('Amiss\Exception', 'Relation type is not assignable');
        $this->manager->assignRelated($artist, 'type');
    }

    function testHasManyGetRelated()
    {
        $type = $this->manager->getById('ArtistType', 1);
        $artists = $this->manager->getRelated($type, 'artists');
        $this->assertInternalType('array', $artists);
        $this->assertCount(1, $artists);
        $this->assertInstanceOf($this->classScopeClassName('Artist'), $artists[0]);
    }

    function testHasManyAssignRelatedFails()
    {
        $type = $this->manager->getById('ArtistType', 1);
        $this->setExpectedException('Amiss\Exception', 'Relation artists is not assignable');
        $this->manager->assignRelated($type, 'artists');
    }
}

