<?php
namespace Amiss\Test\Acceptance;

use Amiss\Test;

/**
 * @group acceptance
 * @group mapper
 */
class MapperTest extends \Amiss\Test\Helper\TestCase
{
    public function setUp()
    {
        $this->deps = Test\Factory::managerModelDemo();
        $this->manager = $this->deps->manager;
    }

    public function tearDown()
    {
        $this->manager = null;
        $this->deps = null;
        parent::tearDown();
    }

    public function testMapperToObjectMeta()
    {
        $mapper = $this->deps->mapper;
        $obj = $mapper->toObject(['artistId'=>1], null, 'Amiss\Demo\Artist');
        $this->assertInstanceOf('Amiss\Demo\Artist', $obj);

        $obj = $mapper->toObject(['artistId'=>1], null, $mapper->getMeta('Amiss\Demo\Artist'));
        $this->assertInstanceOf('Amiss\Demo\Artist', $obj);
    }

    public function testMapperFromObjectMeta()
    {
        $mapper = $this->deps->mapper;
        $a = new \Amiss\Demo\Artist();
        $a->artistId = 1;
        $array = $mapper->fromObject($a, 'Amiss\Demo\Artist');
        $this->assertInternalType('array', $array);

        $array = $mapper->fromObject($a, $mapper->getMeta('Amiss\Demo\Artist'));
        $this->assertInternalType('array', $array);
    }
}
