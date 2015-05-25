<?php
namespace Amiss\Test\Acceptance;

use Amiss\Demo;

class DefaultOrderTest extends \Amiss\Test\Helper\CustomMapperTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->manager = $this->createDefaultArrayManager([
            'Parent'=>[
                'class'  => 'stdClass',
                'table'  => 't1',
                'fields' => [
                    'id'=>['primary'=>true],
                    'orderedId'=>['index'=>true],
                ],
                'relations' => [
                    'ordered' => ['one', 'of'=>'Ordered', 'from'=>'orderedId']
                ],
            ],
            'Ordered'=>[
                'class'   => 'stdClass',
                'table'   => 't2',
                'primary' => 'id',
                'fields'  => [
                    'id'=>true, 'yep'=>true, 'sort'=>true,
                ],
                'defaultOrder'=>'sort',
            ],
        ]);
        $this->manager->insertTable('Ordered', ['id'=>1, 'yep'=>2, 'sort'=>3]);
        $this->manager->insertTable('Ordered', ['id'=>2, 'yep'=>2, 'sort'=>2]);
        $this->manager->insertTable('Ordered', ['id'=>3, 'yep'=>2, 'sort'=>1]);

        $this->manager->insertTable('Parent',  ['id'=>1, 'orderedId'=>2]);
        $this->manager->insertTable('Parent',  ['id'=>2, 'orderedId'=>1]);
        $this->manager->insertTable('Parent',  ['id'=>3, 'orderedId'=>3]);
    }

    /**
     * @group acceptance
     */
    function testDefaultOrderManagerGetList()
    {
        $this->assertOrder([3, 2, 1], $this->manager->getList('Ordered'));
    }

    /**
     * @group acceptance
     */
    function testDefaultOrderManagerGetListWithNullOrder()
    {
        $this->assertOrder([1, 2, 3], $this->manager->getList('Ordered', ['order'=>null]));
    }

    /**
     * @group acceptance
     */
    function testDefaultOrderManagerGetRelatedOne()
    {
        $ps = $this->manager->getList('Parent');
        // 'order'=>null must be explicitly defined to use no ordering, here it is not:
        $os = $this->manager->getRelated($ps, 'ordered', [], $this->manager->getMeta('Parent'));
        $this->assertOrder([3, 2, 1], $os);
    }

    /**
     * @group acceptance
     */
    function testDefaultOrderManagerGetRelatedOneWithNullOrder()
    {
        $ps = $this->manager->getList('Parent');
        // 'order'=>null must be explicitly defined to use no ordering.
        $os = $this->manager->getRelated($ps, 'ordered', ['order'=>null], $this->manager->getMeta('Parent'));
        $this->assertOrder([1, 2, 3], $os);
    }

    function assertOrder($pkOrder, $items, $idField='id')
    {
        $result = [];
        foreach ($items as $p) {
            $result[] = $p->$idField;
        }
        $this->assertEquals($result, $pkOrder);
    }
}
