<?php
namespace Amiss\Test\Unit;

class ManagerTest extends \CustomTestCase
{
    public function setUp()
    {
        parent::setUp();
        
        $this->manager = new \Amiss\Sql\Manager(
            new \Amiss\Sql\Connector('sqlite::memory:'),
            new \Amiss\Mapper\Note
        );
    }

    /**
     * @group unit
     * @group manager
     * 
     * @covers Amiss\Sql\Manager::keyValue
     */
    public function testKeyValueWith2Tuples()
    {
        $input = array(
            array('a', 'b'),
            array('c', 'd'),
        );
        $result = $this->manager->keyValue($input);
        $expected = array(
            'a'=>'b',
            'c'=>'d'
        );
        $this->assertEquals($expected, $result);
    }

    /**
     * @group unit
     * @group manager
     * 
     * @covers Amiss\Sql\Manager::keyValue
     */
    public function testKeyValueWith2TupleKeyOverwriting()
    {
        $input = array(
            array('a', 'b'),
            array('a', 'd'),
        );
        $result = $this->manager->keyValue($input);
        $expected = array(
            'a'=>'d'
        );
        $this->assertEquals($expected, $result);
    }

    /**
     * @group unit
     * @group manager
     * 
     * @covers Amiss\Sql\Manager::keyValue
     */
    public function testKeyValueFromObjectsWithKeyValueProperties()
    {
        $input = array(
            (object)array('a'=>'1', 'c'=>'2'),
            (object)array('a'=>'3', 'c'=>'4'),
        );
        $result = $this->manager->keyValue($input, 'a', 'c');
        $expected = array(
            '1'=>'2',
            '3'=>'4',
        );
        $this->assertEquals($expected, $result);
    }
}
