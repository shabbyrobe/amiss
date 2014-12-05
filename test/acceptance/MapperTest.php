<?php
namespace Amiss\Test\Acceptance;

class MapperTest extends \ModelDataTestCase
{
    /**
     * @group acceptance
     * @group mapper
     */
    public function testMapperToObjectMeta()
    {
        $mapper = $this->getMapper();
        $obj = $mapper->toObject('Amiss\Demo\Artist', [
            'artistId'=>1,
        ]);
        $this->assertInstanceOf('Amiss\Demo\Artist', $obj);

        $obj = $mapper->toObject($mapper->getMeta('Amiss\Demo\Artist'), [
            'artistId'=>1,
        ]);
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
        $array = $mapper->fromObject('Amiss\Demo\Artist', $a);
        $this->assertInternalType('array', $array);

        $array = $mapper->fromObject($mapper->getMeta('Amiss\Demo\Artist'), $a);
        $this->assertInternalType('array', $array);
    }
}
