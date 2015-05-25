<?php
namespace Amiss\Test\Acceptance\AutoRelation;

use Amiss\Sql\TableBuilder;

class CircularTest extends \Amiss\Test\Helper\CustomMapperTestCase
{
    private $manager;

    public function setUp()
    {
        $this->manager = $this->createDefaultArrayManager([
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
        $this->manager->insertTable('Test', ['id'=>1, 'linkedId'=>2]);
        $this->manager->insertTable('Test', ['id'=>2, 'linkedId'=>2]);
        $this->manager->connector->queries = 0;
    }

    public function testCycle()
    {
        $manager = $this->manager;
        $child = $manager->getById('Test', 1);
        $this->assertFalse(isset($child->link->link));
    }
}
