<?php
namespace Amiss\Test\Acceptance;

class MapperTest extends \Amiss\Test\Helper\ModelDataTestCase
{
    /**
     * @group acceptance
     * @group mapper
     */
    public function testMapperToObjectMeta()
    {
        $mapper = $this->getMapper();
        $obj = $mapper->toObject(['artistId'=>1], null, 'Amiss\Demo\Artist');
        $this->assertInstanceOf('Amiss\Demo\Artist', $obj);

        $obj = $mapper->toObject(['artistId'=>1], null, $mapper->getMeta('Amiss\Demo\Artist'));
        $this->assertInstanceOf('Amiss\Demo\Artist', $obj);
    }

    /**
     * @group acceptance
     * @group mapper
     */
    public function testMapperFromObjectMeta()
    {
        $mapper = $this->getMapper();
        $a = new \Amiss\Demo\Artist();
        $a->artistId = 1;
        $array = $mapper->fromObject($a, 'Amiss\Demo\Artist');
        $this->assertInternalType('array', $array);

        $array = $mapper->fromObject($a, $mapper->getMeta('Amiss\Demo\Artist'));
        $this->assertInternalType('array', $array);
    }
}
