<?php
namespace Amiss\Test\Acceptance\Manager;

use Amiss\Test\Factory;

/**
 * @group acceptance
 * @group manager
 */
class ViewTest extends \Amiss\Test\Helper\TestCase
{
    function setUp()
    {
        parent::setUp();
        
        $mappings = [
            'foo' => [
                'table' => 'foo',
                'class' => 'stdClass',
                'fields' => ['fooId' => true, 'foo' => true],
            ],
            'bar' => [
                'table' => 'bar',
                'class' => 'stdClass',
                'fields' => ['barId' => true, 'fooId' => true, 'bar' => true],
            ],
        ];
        $this->deps = Factory::managerArraysModelCustom($mappings);
        $this->deps->mapper->mappings['baz'] = [
            'table' => 'baz',
            'class' => 'stdClass',
            'fields' => [
                'fooId' => true, 
                'barId' => true, 
                'foo'   => true,
                'bar'   => true
            ],
        ];

        $this->deps->connector->query("
            CREATE VIEW baz AS
                SELECT bar.fooId, bar.barId, foo.foo, bar.bar FROM bar
                INNER JOIN foo ON foo.fooId = bar.fooId
        ");

        $this->deps->manager->insertTable('foo', ['fooId' => 1, 'foo' => 'a']);
        $this->deps->manager->insertTable('foo', ['fooId' => 2, 'foo' => 'b']);
        $this->deps->manager->insertTable('bar', ['fooId' => 1, 'barId' => 1, 'bar' => 'a']);
        $this->deps->manager->insertTable('bar', ['fooId' => 1, 'barId' => 2, 'bar' => 'b']);
    }

    function testSelect()
    {
        $expected = [
            (object) ['fooId' => '1', 'barId' => '1', 'foo' => 'a', 'bar' => 'a'],
            (object) ['fooId' => '1', 'barId' => '2', 'foo' => 'a', 'bar' => 'b'],
        ];
        $this->assertEquals($expected, $this->deps->manager->getList('baz'));
    }

    function testUpdateTable()
    {
        if ($this->deps->connector->engine == 'sqlite') {
            // markTestSkipped without the noise
            return $this->assertTrue(true);
        }

        $this->deps->manager->updateTable('baz', [
            'set'   => ['bar' => 'z'],
            'where' => ['foo' => 'a'],
        ]);
        $expected = [
            (object) ['barId' => '1', 'fooId' => '1', 'bar' => 'z'],
            (object) ['barId' => '2', 'fooId' => '1', 'bar' => 'z'],
        ];
        $this->assertEquals($expected, $this->deps->manager->getList('bar'));
    }

    function testInsertTable()
    {
        if ($this->deps->connector->engine == 'sqlite') {
            // markTestSkipped without the noise
            return $this->assertTrue(true);
        }

        $this->deps->manager->insertTable('baz', [
            'barId' => '3', 'fooId' => '1', 'bar' => 'n'
        ]);
        $expected = (object) ['barId' => '3', 'fooId' => '1', 'bar' => 'n', 'foo' => 'a'];
        $this->assertEquals($expected, $this->deps->manager->get('baz', 'barId=3'));
    }

    /**
     * @group faulty
     */
    function testInsert()
    {
        if ($this->deps->connector->engine == 'sqlite') {
            // markTestSkipped without the noise
            return $this->assertTrue(true);
        }
        $baz = (object) [
            'barId' => '3',
            'fooId' => '1',
            'bar' => 'q',
            'foo' => 'a',
        ];
        $this->deps->manager->insert($baz, 'baz');
    }
}
