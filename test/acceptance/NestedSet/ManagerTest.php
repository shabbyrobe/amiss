<?php
namespace Amiss\Test\Acceptance\NestedSet;

require_once __DIR__.'/TestCase.php';

class ManagerTest extends TestCase
{
    function setUp()
    {
        parent::setUp();

        list ($this->nestedSetManager, $this->namespace) = $this->createNestedSetNoteManager('
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

                /** :amiss = {"field": true}; */
                public $treeLeft;

                /** :amiss = {"field": true}; */
                public $treeRight;

                /** :amiss = {"field": true}; */
                public $treeParentId;

                /** :amiss = {"has": {"type": "tree"}}; */
                public $tree;

                /** :amiss = {"has": {"type": "parent"}}; */
                public $parent;
            }
        ');
        $this->manager = $this->nestedSetManager->manager;

        $this->manager->insertTable('Tree', ['id'=>1, 'treeParentId'=>null, 'treeLeft'=>1 , 'treeRight'=>14]);
        $this->manager->insertTable('Tree', ['id'=>2, 'treeParentId'=>1,    'treeLeft'=>2 , 'treeRight'=>9]);
        $this->manager->insertTable('Tree', ['id'=>3, 'treeParentId'=>2,    'treeLeft'=>3 , 'treeRight'=>4]);
        $this->manager->insertTable('Tree', ['id'=>4, 'treeParentId'=>3,    'treeLeft'=>4 , 'treeRight'=>5]);
        $this->manager->insertTable('Tree', ['id'=>5, 'treeParentId'=>2,    'treeLeft'=>6 , 'treeRight'=>7]);
        $this->manager->insertTable('Tree', ['id'=>6, 'treeParentId'=>1,    'treeLeft'=>8 , 'treeRight'=>11]);
        $this->manager->insertTable('Tree', ['id'=>7, 'treeParentId'=>6,    'treeLeft'=>9 , 'treeRight'=>10]);
        $this->manager->insertTable('Tree', ['id'=>8, 'treeParentId'=>1,    'treeLeft'=>12, 'treeRight'=>13]);
    }

    function testGetRelatedTree()
    {
        $parent = $this->manager->getById('Tree', 1);
        $tree = $this->manager->getRelated($parent, 'tree');
        $expectedTree = [1=>[2=>[3=>[4=>true], 5=>true], 6=>[7=>true], 8=>true]];
        $this->assertEquals($expectedTree, $this->idTree($parent, $tree));
    }

    function testGetRelatedParents()
    {
        $node = $this->manager->getById('Tree', 7);
        $parents = $this->manager->getRelated($node, 'parents');
        $this->assertEquals(6, $parents[0]->id);
        $this->assertEquals(1, $parents[1]->id);
        $this->assertCount(2, $parents);
    }

    function testGetRelatedParent()
    {
        $node = $this->manager->getById('Tree', 7);
        $parent = $this->manager->getRelated($node, 'parent');
        $this->assertEquals(6, $parent->id);
    }
}
