<?php
namespace Amiss\Test\Acceptance\Manager;

use Amiss\Test\Helper\ClassBuilder;

class ClassPermissionsTest extends \Amiss\Test\Helper\TestCase
{
    private $manager;

    public function setUp()
    {
        $this->db = new \PDOK\Connector('sqlite::memory:');
        $this->mapper = new \Amiss\Mapper\Note();
        $this->manager = new \Amiss\Sql\Manager($this->db, $this->mapper);
    }

    public function testInsertReadOnlyClassFails()
    {
        $c = ClassBuilder::i()->registerOne('
            /** :amiss = {"readOnly": true}; */
            class Artist {
                /** :amiss = {"field": {"primary": true}}; */
                public $artistId;
            }
        ');
        $a = new $c;
        $a->artistId = 1;
        $this->setExpectedExceptionRegexp(\Amiss\Exception::class, "/Class .* prohibits insert/");
        $this->manager->insert($a);
    }

    public function testUpdateReadOnlyClassFails()
    {
        $c = ClassBuilder::i()->registerOne('
            /** :amiss = {"readOnly": true}; */
            class Artist {
                /** :amiss = {"field": {"primary": true}}; */
                public $artistId;
            }
        ');
        $a = new $c;
        $a->artistId = 1;
        $this->setExpectedExceptionRegexp(\Amiss\Exception::class, "/Class .* prohibits update/");
        $this->manager->update($a);
    }

    public function testSaveReadOnlyClassFails()
    {
        $c = ClassBuilder::i()->registerOne('
            /** :amiss = {"readOnly": true}; */
            class Artist {
                /** :amiss = {"field": { "primary": true, "type": "autoinc" }}; */
                public $artistId;
            }
        ');
        $a = new $c;
        $a->artistId = 1;
        $this->setExpectedExceptionRegexp(\Amiss\Exception::class, "/Class .* prohibits update/");
        $this->manager->save($a);
    }

    public function testDeleteReadOnlyClassFails()
    {
        $c = ClassBuilder::i()->registerOne('
            /** :amiss = {"readOnly": true}; */
            class Artist {
                /** :amiss = {"field": { "primary": true, "type": "autoinc" }}; */
                public $artistId;
            }
        ');
        $a = new $c;
        $a->artistId = 1;
        $this->setExpectedExceptionRegexp(\Amiss\Exception::class, "/Class .* prohibits delete/");
        $this->manager->delete($a);
    }

    public function testCanInsertDisabledFails()
    {
        $c = ClassBuilder::i()->registerOne('
            /** :amiss = {"canInsert": false}; */
            class Artist {
                /** :amiss = {"field": {"primary": true}}; */
                public $artistId;
            }
        ');
        $a = new $c;
        $a->artistId = 1;
        $this->setExpectedExceptionRegexp(\Amiss\Exception::class, "/Class .* prohibits insert/");
        $this->manager->insert($a);
    }

    public function testCanUpdateDisabledFails()
    {
        $c = ClassBuilder::i()->registerOne('
            /** :amiss = {"canUpdate": false}; */
            class Artist {
                /** :amiss = {"field": {"primary": true}}; */
                public $artistId;
            }
        ');
        $a = new $c;
        $a->artistId = 1;
        $this->setExpectedExceptionRegexp(\Amiss\Exception::class, "/Class .* prohibits update/");
        $this->manager->update($a);
    }

    public function testCanInsertDisabledPreventsSave()
    {
        $c = ClassBuilder::i()->registerOne('
            /** :amiss = {"canInsert": false}; */
            class Artist {
                /** :amiss = {"field": { "primary": true, "type": "autoinc" }}; */
                public $artistId;
            }
        ');
        $a = new $c;
        $this->setExpectedExceptionRegexp(\Amiss\Exception::class, "/Class .* prohibits insert/");
        $this->manager->save($a);
    }

    public function testCanUpdateDisabledPreventsSave()
    {
        $c = ClassBuilder::i()->registerOne('
            /** :amiss = {"canUpdate": false}; */
            class Artist {
                /** :amiss = {"field": { "primary": true, "type": "autoinc" }}; */
                public $artistId;
            }
        ');
        $a = new $c;
        $a->artistId = 1;
        $this->setExpectedExceptionRegexp(\Amiss\Exception::class, "/Class .* prohibits update/");
        $this->manager->save($a);
    }

    public function testCanDeleteDisabled()
    {
        $c = ClassBuilder::i()->registerOne('
            /** :amiss = {"canDelete": false}; */
            class Artist {
                /** :amiss = {"field": { "primary": true, "type": "autoinc" }}; */
                public $artistId;
            }
        ');
        $a = new $c;
        $a->artistId = 1;
        $this->setExpectedExceptionRegexp(\Amiss\Exception::class, "/Class .* prohibits delete/");
        $this->manager->delete($a);
    }
}
