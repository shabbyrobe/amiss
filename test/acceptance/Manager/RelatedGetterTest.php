<?php
namespace Amiss\Test\Acceptance\Manager;

use Amiss\Sql\Query\Criteria;

use Amiss\Demo;

/**
 * @group acceptance
 * @group manager
 */
class RelatedGetterTest extends \CustomTestCase
{
    function setUp()
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

    function testGetRelatedGetterOneToOne()
    {
        $c = $this->manager->getById(RelatedGetterTestChild::class, 1);
        $p = $this->manager->getRelated($c, 'parent');
        $this->assertInstanceOf(RelatedGetterTestParent::class, $p);
    }

    function testAssignRelatedGetterOneToOne()
    {
        $c = $this->manager->getById(RelatedGetterTestChild::class, 1);
        $this->manager->assignRelated($c, 'parent');
        $this->assertInstanceOf(RelatedGetterTestParent::class, $c->getParent());
    }

    function testGetRelatedGetterOneToMany()
    {
        $p = $this->manager->getById(RelatedGetterTestParent::class, 1);
        $c = $this->manager->getRelated($p, 'children');
        $this->assertInternalType('array', $c);
        $this->assertCount(2, $c);
        $this->assertInstanceOf(RelatedGetterTestChild::class, $c[0]);
    }

    function testAssignRelatedGetterOneToMany()
    {
        $p = $this->manager->getById(RelatedGetterTestParent::class, 1);
        $this->manager->assignRelated($p, 'children');
        $c = $p->getChildren();
        $this->assertInternalType('array', $c);
        $this->assertCount(2, $c);
        $this->assertInstanceOf(RelatedGetterTestChild::class, $c[0]);
    }
}

/** @table child */
class RelatedGetterTestChild
{
    /** @primary */
    public $id;

    /**
     * @field
     * @index
     */
    public $parentId;

    private $parent;

    /**
     * @has.one.of Amiss\Test\Acceptance\Manager\RelatedGetterTestParent
     * @has.one.from parentId
     */
    public function getParent()
    {
        return $this->parent;
    }

    public function setParent($parent)
    {
        $this->parent = $parent;
    }
}

/**
 * @table parent
 */
class RelatedGetterTestParent
{
    /**
     * @primary
     */
    public $id;

    private $children;

    /**
     * @has.many.of Amiss\Test\Acceptance\Manager\RelatedGetterTestChild
     * @has.many.inverse parent
     */
    public function getChildren()
    {
        return $this->children;
    }

    public function setChildren($children)
    {
        $this->children = $children;
    }    
}
