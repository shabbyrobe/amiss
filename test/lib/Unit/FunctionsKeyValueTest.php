<?php
namespace Amiss\Test\Unit;

use Amiss\Functions;

/**
 * @group unit
 */
class FunctionsKeyValueTest extends \Amiss\Test\Helper\TestCase
{
    /**
     * @group functions
     * @covers Amiss\Functions::keyValue
     */
    public function testKeyValueWith2Tuples()
    {
        $input = array(
            array('a', 'b'),
            array('c', 'd'),
        );
        $result = Functions::keyValue($input);
        $expected = array(
            'a'=>'b',
            'c'=>'d'
        );
        $this->assertEquals($expected, $result);
    }

    /**
     * @group functions
     * 
     * @covers Amiss\Functions::keyValue
     */
    public function testKeyValueWith2TupleKeyOverwriting()
    {
        $input = array(
            array('a', 'b'),
            array('a', 'd'),
        );
        $result = Functions::keyValue($input);
        $expected = array(
            'a'=>'d'
        );
        $this->assertEquals($expected, $result);
    }

    /**
     * @group functions
     * @covers Amiss\Functions::keyValue
     */
    public function testKeyValueFromObjectsWithKeyValueProperties()
    {
        $input = array(
            (object)array('a'=>'1', 'c'=>'2'),
            (object)array('a'=>'3', 'c'=>'4'),
        );
        $result = Functions::keyValue($input, 'a', 'c');
        $expected = array(
            '1'=>'2',
            '3'=>'4',
        );
        $this->assertEquals($expected, $result);
    }
}
