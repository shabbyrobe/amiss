<?php
namespace Amiss\Test\Acceptance\AutoRelation;

use Amiss\Sql\TableBuilder;
use Amiss\Test;

class CircularTest extends \Amiss\Test\Helper\TestCase
{
    private $manager;

    public function setUp()
    {
        $this->deps = Test\Factory::managerArraysModelCustom([
            'Test'=>[
                'class'=>'stdClass',
                'fields'=>[
                    'id'=>['primary'=>true],
                    'linkedId'=>['index'=>true],
                ],
                'relations'=>[
                    'link'=>['one', 'of'=>'Test', 'from'=>'linkedId', 'mode'=>'auto'],
                ],

            ],
        ]);
        $this->deps->manager->insertTable('Test', ['id'=>1, 'linkedId'=>2]);
        $this->deps->manager->insertTable('Test', ['id'=>2, 'linkedId'=>2]);
        $this->deps->manager->connector->queries = 0;
    }

    public function testCycle()
    {
        $manager = $this->deps->manager;
        $child = $manager->getById('Test', 1);
        $this->assertFalse(isset($child->link->link));
    }
}
