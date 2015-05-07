<?php
namespace Amiss\Test\Acceptance\Manager;

class ReadOnlyClassTest extends \CustomTestCase
{
    private $manager;

    public function setUp()
    {
        self::createClassScopeClass('Artist', '
            /**
             * @readOnly
             */
            class Artist {
                /** 
                 * @primary 
                 * @type autoinc
                 */ public $artistId;
            }
        ');

        $this->db = new \PDOK\Connector('sqlite::memory:');
        $this->mapper = new \Amiss\Mapper\Note();
        $this->manager = new \Amiss\Sql\Manager($this->db, $this->mapper);
    }

    public function testInsertReadOnlyClassFails()
    {
        $c = self::classScopeClassName('Artist');
        $a = new $c;
        $a->artistId = 1;

        $this->setExpectedException("\Amiss\Exception", "Cannot insert read only class");
        $this->manager->insert($a);
    }

    public function testUpdateReadOnlyClassFails()
    {
        $c = self::classScopeClassName('Artist');
        $a = new $c;
        $a->artistId = 1;

        $this->setExpectedException("\Amiss\Exception", "Cannot update read only class");
        $this->manager->update($a);
    }

    public function testSaveReadOnlyClassFails()
    {
        $c = self::classScopeClassName('Artist');
        $a = new $c;
        $a->artistId = 1;

        $this->setExpectedException("\Amiss\Exception", "Cannot update read only class");
        $this->manager->save($a);
    }
}
