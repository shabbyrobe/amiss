<?php
namespace Amiss\Test\Acceptance;

use Amiss\Test;
use Amiss\Test\Helper\ClassBuilder;

/**
 * @group manager
 */
class GroupByTest extends \Amiss\Test\Helper\TestCase
{
    public function setUp()
    {
        $obj1 = new \Amiss\Demo\Artist;
        $obj1->name = 'a';
        $obj1->artistId = 1;
        $obj2 = new \Amiss\Demo\Artist;
        $obj2->name = 'b';
        $obj2->artistId = 2;
        $obj3 = new \Amiss\Demo\Artist;
        $obj3->artistId = 3;
        $obj3->name = 'b';

        $this->objects = [$obj1, $obj2, $obj3];
    }

    /**
     * @covers Amiss\Sql\Manager::groupBy
     */
    public function testGroupBy()
    {
        $deps = Test\Factory::managerModelDemo();

        $indexed = $deps->manager->groupBy($this->objects, 'name');
        $expected = [
            'a'=>[$this->objects[0]],
            'b'=>[$this->objects[1], $this->objects[2]]
        ];
        $this->assertEquals($expected, $indexed);
    }
}
