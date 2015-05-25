<?php
namespace Amiss\Test\Acceptance\Manager;

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
        $c = self::createFnScopeClass('Artist', '
            /** :amiss = {"readOnly": true}; */
            class Artist {
                /** :amiss = {"field": {"primary": true}}; */
                public $artistId;
            }
        ');
        $a = new $c;
        $a->artistId = 1;
        $this->setExpectedExceptionRegexp("\Amiss\Exception", "/Class .* prohibits insert/");
        $this->manager->insert($a);
    }

    public function testUpdateReadOnlyClassFails()
    {
        $c = self::createFnScopeClass('Artist', '
            /** :amiss = {"readOnly": true}; */
            class Artist {
                /** :amiss = {"field": {"primary": true}}; */
                public $artistId;
            }
        ');
        $a = new $c;
        $a->artistId = 1;
        $this->setExpectedExceptionRegexp("\Amiss\Exception", "/Class .* prohibits update/");
        $this->manager->update($a);
    }

    public function testSaveReadOnlyClassFails()
    {
        $c = self::createFnScopeClass('Artist', '
            /** :amiss = {"readOnly": true}; */
            class Artist {
                /** :amiss = {"field": { "primary": true, "type": "autoinc" }}; */
                public $artistId;
            }
        ');
        $a = new $c;
        $a->artistId = 1;
        $this->setExpectedExceptionRegexp("\Amiss\Exception", "/Class .* prohibits update/");
        $this->manager->save($a);
    }

    public function testDeleteReadOnlyClassFails()
    {
        $c = self::createFnScopeClass('Artist', '
            /** :amiss = {"readOnly": true}; */
            class Artist {
                /** :amiss = {"field": { "primary": true, "type": "autoinc" }}; */
                public $artistId;
            }
        ');
        $a = new $c;
        $a->artistId = 1;
        $this->setExpectedExceptionRegexp("\Amiss\Exception", "/Class .* prohibits delete/");
        $this->manager->delete($a);
    }

    public function testCanInsertDisabledFails()
    {
        $c = self::createFnScopeClass('Artist', '
            /** :amiss = {"canInsert": false}; */
            class Artist {
                /** :amiss = {"field": {"primary": true}}; */
                public $artistId;
            }
        ');
        $a = new $c;
        $a->artistId = 1;
        $this->setExpectedExceptionRegexp("\Amiss\Exception", "/Class .* prohibits insert/");
        $this->manager->insert($a);
    }

    public function testCanUpdateDisabledFails()
    {
        $c = self::createFnScopeClass('Artist', '
            /** :amiss = {"canUpdate": false}; */
            class Artist {
                /** :amiss = {"field": {"primary": true}}; */
                public $artistId;
            }
        ');
        $a = new $c;
        $a->artistId = 1;
        $this->setExpectedExceptionRegexp("\Amiss\Exception", "/Class .* prohibits update/");
        $this->manager->update($a);
    }

    public function testCanInsertDisabledPreventsSave()
    {
        $c = self::createFnScopeClass('Artist', '
            /** :amiss = {"canInsert": false}; */
            class Artist {
                /** :amiss = {"field": { "primary": true, "type": "autoinc" }}; */
                public $artistId;
            }
        ');
        $a = new $c;
        $this->setExpectedExceptionRegexp("\Amiss\Exception", "/Class .* prohibits insert/");
        $this->manager->save($a);
    }

    public function testCanUpdateDisabledPreventsSave()
    {
        $c = self::createFnScopeClass('Artist', '
            /** :amiss = {"canUpdate": false}; */
            class Artist {
                /** :amiss = {"field": { "primary": true, "type": "autoinc" }}; */
                public $artistId;
            }
        ');
        $a = new $c;
        $a->artistId = 1;
        $this->setExpectedExceptionRegexp("\Amiss\Exception", "/Class .* prohibits update/");
        $this->manager->save($a);
    }

    public function testCanDeleteDisabled()
    {
        $c = self::createFnScopeClass('Artist', '
            /** :amiss = {"canDelete": false}; */
            class Artist {
                /** :amiss = {"field": { "primary": true, "type": "autoinc" }}; */
                public $artistId;
            }
        ');
        $a = new $c;
        $a->artistId = 1;
        $this->setExpectedExceptionRegexp("\Amiss\Exception", "/Class .* prohibits delete/");
        $this->manager->delete($a);
    }
}
