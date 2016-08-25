<?php
namespace Amiss\Test\Acceptance\Manager;

use Amiss\Test;

class KeywordTableNameTest extends \Amiss\Test\Helper\TestCase
{
    function testCreate()
    {
        $d = Test\Factory::managerArraysModelCustom([
            'Pants'=>[
                'table'=>'ORDER BY',
                'class'=>'stdClass',
                'fields'=>[
                    'foo'=>true,
                ],
            ],
        ]);
        return $d;
    }

    /** @depends testCreate */
    function testInsertTable($d)
    {
        $d->manager->insertTable('Pants', ['foo'=>'abc']);
        $d->manager->insertTable('Pants', ['foo'=>'def']);
        $d->manager->insertTable('Pants', ['foo'=>'ghi']);
        $rows = $d->manager->connector->query("SELECT * FROM `ORDER BY`")->fetchAll(\PDO::FETCH_ASSOC);
        $expected = [
            ['foo'=>'abc'],
            ['foo'=>'def'],
            ['foo'=>'ghi'],
        ];
        $this->assertEquals($expected, $rows);

        return $d;
    }

    /** @depends testInsertTable */
    function testGet($d)
    {
        $obj = $d->manager->get('Pants', ['where'=>['foo'=>'abc']]);
        $this->assertEquals('abc', $obj->foo);
    }
}
