<?php
namespace Amiss\Test\Acceptance\AutoRelation;

class OneManyTest extends \CustomTestCase
{
    public function test()
    {
        $this->db = new \Amiss\Sql\Connector('sqlite::memory:');
        $this->db->exec("CREATE TABLE child(id INTEGER, parentId INTEGER)");
        $this->db->exec("CREATE TABLE parent(id INTEGER)");
        $this->db->exec("INSERT INTO child VALUES(1, 1)");
        $this->db->exec("INSERT INTO child VALUES(2, 1)");
        $this->db->exec("INSERT INTO parent VALUES(1)");
        $this->mapper = new \Amiss\Mapper\Note;
        $this->manager = new \Amiss\Sql\Manager($this->db, $this->mapper);
        $this->manager->relators = \Amiss::createSqlRelators();
    }
}
