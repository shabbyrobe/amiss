<?php
namespace Amiss\Test\Acceptance\AutoRelation;

use Amiss\Sql\TableBuilder;

class OneManyTest extends \CustomTestCase
{
    private $manager;

    public function setUp()
    {
        $this->db = new \Amiss\Sql\Connector('sqlite::memory:');
    }

    public function getManager()
    {
        if (!$this->manager) {
            $this->mapper = $this->createDefaultMapper();
            $this->mapper->objectNamespace = __NAMESPACE__;
            $this->manager = new \Amiss\Sql\Manager($this->db, $this->mapper);
            $this->manager->relators = \Amiss::createSqlRelators();
            foreach ($this->mapper->arrayMap as $class=>$meta) {
                TableBuilder::create($this->manager->connector, $this->mapper, $class);
            }
            $this->createDefaultData();
        }
        return $this->manager;
    }

    public function createDefaultData()
    {
        $this->manager->connector->exec("INSERT INTO test_child VALUES(1, 1)");
        $this->manager->connector->exec("INSERT INTO test_child VALUES(2, 1)");
        $this->manager->connector->exec("INSERT INTO test_parent VALUES(1, 1)");
        $this->manager->connector->exec("INSERT INTO test_grand_parent VALUES(1)");
    }

    public function createDefaultMapper()
    {
        return new \Amiss\Mapper\Arrays([
            __NAMESPACE__.'\TestChild'=>[
                'primary'=>'id',
                'fields'=>[
                    'id'=>['type'=>'autoinc'],
                    'parentId'=>['type'=>'int'],
                ],
                'indexes'=>['parent'=>['fields'=>'parentId']],
                'relations'=>[
                    'parent'=>['one', 'of'=>'TestParent', 'from'=>'parent'],
                ],
            ],
            __NAMESPACE__.'\TestParent'=>[
                'primary'=>'id',
                'fields'=>[
                    'id'=>['type'=>'autoinc'],
                    'grandParentId'=>['type'=>'int'],
                ],
                'indexes'=>['grandParent'=>['fields'=>'grandParentId']],
                'relations'=>[
                    'children'=>['many', 'of'=>'TestChild', 'to'=>'parent'],
                    'grandParent'=>['one', 'of'=>'TestGrandParent', 'from'=>'grandParent'],
                ],
            ],
            __NAMESPACE__.'\TestGrandParent'=>[
                'primary'=>'id',
                'fields'=>['id'=>['type'=>'autoinc']],
                'relations'=>[
                    'parents'=>['many', 'of'=>'TestParent', 'to'=>'grandParent'],
                ],
            ],
        ]);
    }

    public function setAutoRelation($class, $relation, $inverse=null)
    {
        $meta = $this->mapper->getMeta($class);
        $meta->autoRelations[] = $relation;
        $relatedMeta = $this->mapper->getMeta($meta->relations[$relation]['of']);

        if ($inverse) {
            $relatedMeta->autoRelations[] = $inverse;
        }
    }

    public function testAutoOne()
    {
        $manager = $this->getManager();
        $this->setAutoRelation('TestChild', 'parent');

        $child = $manager->getById('TestChild', 1);
        $this->assertTrue($child->parent instanceof TestParent);
    }

    public function testAutoOneDoesntCycle()
    {
        $manager = $this->getManager();
        $this->setAutoRelation('TestChild', 'parent', 'children');

        $child = $manager->getById('TestChild', 1);
        $this->assertTrue($child->parent instanceof TestParent);
        $this->assertNull($child->parent->children);
    }

    public function testAutoOneDoesntCycleDeep()
    {
        $manager = $this->getManager();
        $this->setAutoRelation('TestChild', 'parent', 'children');
        $this->setAutoRelation('TestGrandParent', 'parents', 'grandParent');

        $child = $manager->getById('TestChild', 1);
        $this->assertTrue($child->parent instanceof TestParent);
        $this->assertTrue($child->parent->grandParent instanceof TestGrandParent);
        $this->assertNull($child->parent->children);
    }

    public function testAutoMany()
    {
        $manager = $this->getManager();
        $this->setAutoRelation('TestChild', 'parent', 'children');
        $this->setAutoRelation('TestGrandParent', 'parents', 'grandParent');

        $manager = $this->getManager();

        $parent = $manager->getById('TestParent', 1);
        $this->assertTrue($parent->children[0] instanceof TestChild);
    }

    public function testAutoManyDoesntCycle()
    {
        $manager = $this->getManager();
        $this->setAutoRelation('TestChild', 'parent', 'children');
        $this->setAutoRelation('TestGrandParent', 'parents', 'grandParent');

        $parent = $manager->getById('TestParent', 1);
        $this->assertTrue($parent->children[0] instanceof TestChild);
        $this->assertTrue($parent->grandParent instanceof TestGrandParent);
        $this->assertNull($parent->children[0]->parent);
    }
}

class TestGrandParent extends \DummyClass {}
class TestParent extends \DummyClass {}
class TestChild extends \DummyClass {}

