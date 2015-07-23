<?php
namespace Amiss\Test\Acceptance\AutoRelation;

use Amiss\Test;

class OneManySetterTest extends \Amiss\Test\Helper\TestCase
{
    function setUp()
    {
        $this->deps = Test\Factory::managerNoteModelCustom('
            class TestParent {
                /** :amiss = {"field": {"primary": true}}; */ public $id;
                
                private $children = "nope";

                /** :amiss = {"has": {"type": "many", "of": "TestChild", "to": "parentId"}}; */
                public function getChildren()   { return $this->children; }
                public function setChildren($v) { $this->children = $v; }
            }
            class TestChild {
                /** :amiss = {"field": {"primary": true}}; */ public $id;
                /** :amiss = {"field": {"index": true}};   */ public $parentId;

                private $parent = "nope";

                /** :amiss = {"has": {"type": "one", "of": "TestParent", "from": "parentId"}}; */
                public function getParent()   { return $this->parent; }
                public function setParent($v) { $this->parent = $v; }
            }
        ');
        $this->deps->manager->insertTable('TestParent', ['id'=>1]);
        $this->deps->manager->insertTable('TestChild' , ['id'=>1, 'parentId'=>1]);
        $this->deps->manager->insertTable('TestChild' , ['id'=>2, 'parentId'=>1]);
    }

    function tearDown() { $this->deps = null; }

    function testAutoManyQuery()
    {
        $parent = $this->deps->manager->getById('TestParent', 1, ['with'=>'children']);
        $this->assertInternalType('array', $parent->getChildren());
        $this->assertCount(2, $parent->getChildren());
    }

    function testAutoManyQueryNoRelatedStillCallsSetter()
    {
        // insert a parent with no children
        $this->deps->manager->insertTable('TestParent', ['id'=>2]);

        $parent = $this->deps->manager->getById('TestParent', 2, ['with'=>'children']);
        $this->assertInternalType('array', $parent->getChildren());
        $this->assertCount(0, $parent->getChildren());
    }
}
