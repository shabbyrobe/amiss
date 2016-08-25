<?php
namespace Amiss\Test\Acceptance\Manager;

use Amiss\Sql\Query;
use Amiss\Test;
use Amiss\Functions as AF;

/**
 * @group acceptance
 * @group manager
 */
class SelectListTest extends \Amiss\Test\Helper\TestCase
{
    function setUp()
    {
        $this->deps = $deps = Test\Factory::managerArraysModelCustom([
            'Pants'=>[
                'table'=>'yep', 'class'=>'stdClass',
                'fields'=>['foo'=>true, 'bar'=>true, 'baz'=>true],
            ],
        ]);
        $deps->manager->insertTable('Pants', ['foo'=>'abc', 'bar'=>'123', 'baz'=>'!!!']);
        $deps->manager->insertTable('Pants', ['foo'=>'bcd', 'bar'=>'234', 'baz'=>'@@@']);
        $deps->manager->insertTable('Pants', ['foo'=>'cde', 'bar'=>'345', 'baz'=>'###']);
    }

    function testSelectListMultipleColumns()
    {
        $result = $this->deps->manager->selectList('Pants', ['foo', 'bar']);
        $expected = [
            (object)['foo'=>'abc', 'bar'=>'123'],
            (object)['foo'=>'bcd', 'bar'=>'234'],
            (object)['foo'=>'cde', 'bar'=>'345'],
        ];
        $this->assertEquals($expected, $result);
    }

    function testSelectListSingleArrayColumn()
    {
        $result = $this->deps->manager->selectList('Pants', ['foo']);
        $expected = [
            (object)['foo'=>'abc'],
            (object)['foo'=>'bcd'],
            (object)['foo'=>'cde'],
        ];
        $this->assertEquals($expected, $result);
    }

    function testSelectListSingleStringColumn()
    {
        $result = $this->deps->manager->selectList('Pants', 'foo');
        $expected = [
            'abc',
            'bcd',
            'cde',
        ];
        $this->assertEquals($expected, $result);
    }

    function testSelectListFieldsAndArrayQuery()
    {
        $query = ['where'=>'foo like "%b%"'];
        $result = $this->deps->manager->selectList('Pants', 'foo', $query);
        $expected = ['abc', 'bcd'];
        $this->assertEquals($expected, $result);
    }

    function testSelectListFieldsAndInstanceQuery()
    {
        $query = new Query\Select(['where'=>'foo like "%b%"']);
        $result = $this->deps->manager->selectList('Pants', 'foo', $query);
        $expected = ['abc', 'bcd'];
        $this->assertEquals($expected, $result);
    }

    function testSelectListInstanceQueryContainingStringFields()
    {
        $query = new Query\Select(['where'=>'foo like "%b%"', 'fields'=>'foo']);
        $result = $this->deps->manager->selectList('Pants', $query);
        $expected = ['abc', 'bcd'];
        $this->assertEquals($expected, $result);
    }

    function testSelectListInstanceQueryContainingArrayFields()
    {
        $query = new Query\Select(['where'=>'foo like "%b%"', 'fields'=>['foo']]);
        $result = $this->deps->manager->selectList('Pants', $query);
        $expected = [
            (object)['foo'=>'abc'],
            (object)['foo'=>'bcd'],
        ];
        $this->assertEquals($expected, $result);
    }

    function testSelectListInstanceQueryWrongType()
    {
        $query = new Query\Criteria(['where'=>'foo like "%b%"']);
        $this->setExpectedException(\InvalidArgumentException::class);
        $result = $this->deps->manager->selectList('Pants', $query);
    }

    function testSelectListKeyValue()
    {
        $manager = $this->deps->manager;
        $result = AF::keyValue($manager->selectList('Pants', ['foo', 'bar']), 'foo', 'bar');
        $expected = [
            'abc'=>'123',
            'bcd'=>'234',
            'cde'=>'345',
        ];
        $this->assertSame($expected, $result);
    }

    function testSelectListKeyValueWhenNothingReturned()
    {
        $manager = $this->deps->manager;
        $result = AF::keyValue($manager->selectList('Pants', ['foo', 'bar'], ['where'=>'1=0']), 'foo', 'bar');
        $expected = [];
        $this->assertSame($expected, $result);
    }
}
