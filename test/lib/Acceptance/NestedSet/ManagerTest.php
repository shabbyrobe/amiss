<?php
namespace Amiss\Test\Acceptance\NestedSet;

require_once __DIR__.'/TestCase.php';

class ManagerTest extends TestCase
{
    function setUp()
    {
        parent::setUp();

        $this->deps = self::managerNestedSetNote('
            /** 
             * :amiss = {
             *     "ext": {
             *         "nestedSet": {
             *             "leftId": "treeLeft",
             *             "rightId": "treeRight",
             *             "parentId": "treeParentId"
             *          }
             *      },
             *      "indexes": {
             *          "parentId": {"fields": ["treeParentId"]}
             *      },
             *      "relations": {
             *          "parents": {"type": "parents"},
             *          "children": {"type": "many", "of": "Tree", "from": "parentId"}
             *      }
             * };
             */
            class Tree {
                /** :amiss = {"field": {"primary": true}}; */
                public $id;

                /** :amiss = {"field": {"type": "integer"}}; */
                public $treeLeft;

                /** :amiss = {"field": {"type": "integer"}}; */
                public $treeRight;

                /** :amiss = {"field": {"type": "integer"}}; */
                public $treeParentId;

                /** :amiss = {"has": {"type": "tree"}}; */
                public $tree;

                /** :amiss = {"has": {"type": "parent"}}; */
                public $parent;
            }
        ');

        $m = $this->deps->manager;
        $m->insertTable('Tree', ['id'=>1, 'treeParentId'=>null, 'treeLeft'=>1 , 'treeRight'=>16]);
        $m->insertTable('Tree', ['id'=>2, 'treeParentId'=>1,    'treeLeft'=>2 , 'treeRight'=>9]);
        $m->insertTable('Tree', ['id'=>3, 'treeParentId'=>2,    'treeLeft'=>3 , 'treeRight'=>6]);
        $m->insertTable('Tree', ['id'=>4, 'treeParentId'=>3,    'treeLeft'=>4 , 'treeRight'=>5]);
        $m->insertTable('Tree', ['id'=>5, 'treeParentId'=>2,    'treeLeft'=>7 , 'treeRight'=>8]);
        $m->insertTable('Tree', ['id'=>6, 'treeParentId'=>1,    'treeLeft'=>10, 'treeRight'=>13]);
        $m->insertTable('Tree', ['id'=>7, 'treeParentId'=>6,    'treeLeft'=>11, 'treeRight'=>12]);
        $m->insertTable('Tree', ['id'=>8, 'treeParentId'=>1,    'treeLeft'=>14, 'treeRight'=>15]);
    }

    function tearDown()
    {
        $this->deps = null;
        parent::tearDown();
    }

    function testGetRelatedTree()
    {
        $parent = $this->deps->manager->getById('Tree', 1);
        $tree = $this->deps->manager->getRelated($parent, 'tree');
        $expectedTree = [1=>[2=>[3=>[4=>true], 5=>true], 6=>[7=>true], 8=>true]];
        $this->assertEquals($expectedTree, $this->idTree($parent, $tree));
    }

    function testGetRelatedTrees()
    {
        $parents = $this->deps->manager->getList('Tree', 'id=3 or id=4 or id=6');
        $trees = $this->deps->manager->getRelated($parents, 'tree');
        $expectedTrees = [
            [3=>[4=>true]],
            [4=>true],
            [6=>[7=>true]],
        ];
        $resultTrees = [];
        foreach ($trees as $idx=>$tree) {
            $resultTrees[] = $this->idTree($parents[$idx], $tree);
        }
        $this->assertEquals($expectedTrees, $resultTrees);
    }

    function testGetRelatedParents()
    {
        $node = $this->deps->manager->getById('Tree', 7);
        $parents = $this->deps->manager->getRelated($node, 'parents');
        $this->assertEquals(6, $parents[0]->id);
        $this->assertEquals(1, $parents[1]->id);
        $this->assertCount(2, $parents);
    }

    function testGetRelatedParent()
    {
        $node = $this->deps->manager->getById('Tree', 7);
        $parent = $this->deps->manager->getRelated($node, 'parent');
        $this->assertEquals(6, $parent->id);
    }

    function testRenumber()
    {
        $initialRows = $this->deps->manager->getList('Tree');
        $this->assertCount(8, $initialRows);

        $this->deps->manager->updateTable('Tree', 'treeLeft=treeLeft+10, treeRight=treeRight+20', '1=1');
        $rows = $this->deps->manager->getList('Tree');
        // sanity check
        $this->assertEquals(11, $rows[0]->treeLeft);

        $this->deps->nsManager->renumber('Tree', !'clone');

        $this->assertEquals($initialRows, $this->deps->manager->getList('Tree'));
    }
}
