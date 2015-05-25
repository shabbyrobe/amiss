<?php
namespace Amiss\Test\Acceptance\Manager;

class KeywordTableNameTest extends \Amiss\Test\Helper\CustomMapperTestCase
{
    function testCreate()
    {
        $manager = $this->createDefaultArrayManager([
            'Pants'=>[
                'table'=>'ORDER BY',
                'fields'=>[
                    'foo'=>true,
                ],
            ],
        ]);
        return $manager;
    }

    /** @depends testCreate */
    function testInsertTable($manager)
    {
        $manager->insertTable('Pants', ['foo'=>'abc']);
        $manager->insertTable('Pants', ['foo'=>'def']);
        $manager->insertTable('Pants', ['foo'=>'ghi']);
        $rows = $manager->connector->query("SELECT * FROM `ORDER BY`")->fetchAll(\PDO::FETCH_ASSOC);
        $expected = [
            ['foo'=>'abc'],
            ['foo'=>'def'],
            ['foo'=>'ghi'],
        ];
        $this->assertEquals($expected, $rows);

        return $manager;
    }

    /** @depends testInsertTable */
    function testGet($manager)
    {
        $obj = $manager->get('Pants', ['where'=>['foo'=>'abc']]);
        $this->assertEquals('abc', $obj->foo);
    }
}
