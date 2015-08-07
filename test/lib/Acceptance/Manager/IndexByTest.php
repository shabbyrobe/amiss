<?php
namespace Amiss\Test\Acceptance;

use Amiss\Test;

class IndexByTest extends \Amiss\Test\Helper\TestCase
{
    /**
     * @covers Amiss\Sql\Manager::indexBy
     */
    public function testIndexByWithoutMeta()
    {
        $deps = Test\Factory::managerModelDemo();

        $obj1 = new \Amiss\Demo\Artist;
        $obj1->name = 'a';
        $obj2 = new \Amiss\Demo\Artist;
        $obj2->name = 'b';

        $indexed = $deps->manager->indexBy([$obj1, $obj2], 'name');
        $expected = ['a'=>$obj1, 'b'=>$obj2];
        $this->assertEquals($expected, $indexed);
    }

    /**
     * @covers Amiss\Sql\Manager::indexBy
     */
    public function testIndexByWithMeta()
    {
        $deps = Test\Factory::managerModelDemo();

        $obj1 = new \Amiss\Demo\Artist;
        $obj1->name = 'a';
        $obj2 = new \Amiss\Demo\Artist;
        $obj2->name = 'b';

        $meta = $deps->mapper->getMeta(\Amiss\Demo\Artist::class);

        $indexed = $deps->manager->indexBy([$obj1, $obj2], 'name', $meta);
        $expected = ['a'=>$obj1, 'b'=>$obj2];
        $this->assertEquals($expected, $indexed);
    }

    /**
     * @covers Amiss\Sql\Manager::indexBy
     */
    public function testIndexByPreventsDupes()
    {
        $deps = Test\Factory::managerModelDemo();

        $obj1 = new \Amiss\Demo\Artist;
        $obj1->name = 'a';
        $obj2 = new \Amiss\Demo\Artist;
        $obj2->name = 'a';

        $this->setExpectedException(\UnexpectedValueException::class, "Duplicate value");
        $indexed = $deps->manager->indexBy([$obj1, $obj2], 'name');
    }

    /**
     * @covers Amiss\Sql\Manager::indexBy
     */
    public function testIndexByAllowDupes()
    {
        $deps = Test\Factory::managerModelDemo();

        $obj1 = new \Amiss\Demo\Artist;
        $obj1->name = 'a';
        $obj2 = new \Amiss\Demo\Artist;
        $obj2->name = 'a';

        $indexed = $deps->manager->indexBy([$obj1, $obj2], 'name', null, !!'allowDupes');
        $expected = ['a'=>$obj2];
        $this->assertEquals($expected, $indexed);
    }

    /**
     * @covers Amiss\Sql\Manager::indexBy
     */
    public function testIndexByIgnoresNulls()
    {
        $deps = Test\Factory::managerModelDemo();

        $obj1 = new \Amiss\Demo\Artist;
        $obj1->name = null;
        $obj2 = new \Amiss\Demo\Artist;
        $obj2->name = 'a';

        $indexed = $deps->manager->indexBy([$obj1, $obj2], 'name');
        $expected = ['a'=>$obj2];
        $this->assertEquals($expected, $indexed);
    }

    /**
     * @covers Amiss\Sql\Manager::indexBy
     */
    public function testIndexByAllowedNulls()
    {
        $deps = Test\Factory::managerModelDemo();

        $obj1 = new \Amiss\Demo\Artist;
        $obj1->name = null;
        $obj2 = new \Amiss\Demo\Artist;
        $obj2->name = 'a';

        $indexed = $deps->manager->indexBy([$obj1, $obj2], 'name', null, null, !'ignoreNulls');
        $expected = [''=>$obj1, 'a'=>$obj2];
        $this->assertEquals($expected, $indexed);
    }
}
