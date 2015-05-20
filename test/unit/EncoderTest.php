<?php
namespace Amiss\Test\Unit;

use Amiss\Type;
use Amiss\Sql;

/**
 * @group unit
 */
class EncoderTest extends \CustomTestCase
{
    public function testCreateColumnTypeDefault()
    {
        $encoder = new Type\Encoder('serialize', 'unserialize');
        $result = $encoder->createColumnType('sqlite', []);
        $this->assertEquals('TEXT', $result);
    }

    public function testCreateColumnTypeCustomString()
    {
        $encoder = new Type\Encoder('serialize', 'unserialize');
        $encoder->columnType = 'PANTS';
        $result = $encoder->createColumnType('sqlite', []);
        $this->assertEquals('PANTS', $result);
    }

    public function testCreateColumnTypeClosure()
    {
        $encoder = new Type\Encoder('serialize', 'unserialize');
        $encoder->columnType = function($engine) {
            $this->assertEquals('sqlite', $engine);
            return 'PANTS';
        };
        $result = $encoder->createColumnType('sqlite', []);
        $this->assertEquals('PANTS', $result);
    }

    /**
     * @dataProvider dataForEncodeDecode
     */
    public function testEncodeDecode($enc, $raw, $serialised)
    {
        $obj = new \stdClass;

        $result = $enc->prepareValueForDb($raw, array());
        $this->assertEquals($result, $serialised);

        $result = $enc->handleValueFromDb($serialised, array(), array());
        $this->assertEquals($result, $raw);
    }

    public function dataForEncodeDecode()
    {
        $tests[] = $this->dataForJsonEncode();
        $tests[] = $this->dataForClosureEncode();
        $tests[] = $this->dataForSerialise();
        $tests[] = $this->dataForNested();

        $return = array();
        foreach ($tests as $test) {
            foreach ($test[1] as $t) {
                array_unshift($t, $test[0]);
                $return[] = $t;
            }
        }

        return $return;
    }
    
    public function dataForNested()
    {
        $inner = new Sql\Type\Date('datetime', 'UTC', 'UTC');
        
        return array(new Type\Encoder('serialize', 'unserialize', $inner), array(
            array(new \DateTime('2012-01-01', new \DateTimeZone('UTC')), 's:19:"2012-01-01 00:00:00";'),
            array(null, null),
        ));
    }

    public function dataForSerialise()
    {
        return array(new Type\Encoder('serialize', 'unserialize'), array(
            array('1', 's:1:"1";'),
            array('foo', 's:3:"foo";'),
            array(array(1, 2, 3), 'a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}'),
            array(array('a', 'b', array('c', 'd')), 'a:3:{i:0;s:1:"a";i:1;s:1:"b";i:2;a:2:{i:0;s:1:"c";i:1;s:1:"d";}}'),
        ));
    }

    public function dataForJsonEncode()
    {
        return array(new Type\Encoder('json_encode', 'json_decode'), array(
            array('1', '"1"'),
            array('foo', '"foo"'),
            array(array(1, 2, 3), '[1,2,3]'),
            array(array('a', 'b', array('c', 'd')), '["a","b",["c","d"]]'),
        ));
    }

    public function dataForClosureEncode()
    {
        $tests = $this->dataForJsonEncode();
        $tests[0] = new Type\Encoder(
            function($value) { return json_encode($value); },
            function($value) { return json_decode($value); }
        );
        return $tests;
    }
}
