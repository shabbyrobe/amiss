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
        $obj = $mapper->mapRowToObject(['artistId'=>1], null, 'Amiss\Demo\Artist');
        $this->assertInstanceOf('Amiss\Demo\Artist', $obj);

        $obj = $mapper->mapRowToObject(['artistId'=>1], null, $mapper->getMeta('Amiss\Demo\Artist'));
        $this->assertInstanceOf('Amiss\Demo\Artist', $obj);
    }

    public function testMapperFromObjectMeta()
    {
        $mapper = $this->deps->mapper;
        $a = new \Amiss\Demo\Artist();
        $a->artistId = 1;
        $array = $mapper->mapObjectToRow($a, 'Amiss\Demo\Artist');
        $this->assertInternalType('array', $array);

        $array = $mapper->mapObjectToRow($a, $mapper->getMeta('Amiss\Demo\Artist'));
        $this->assertInternalType('array', $array);
    }
}
