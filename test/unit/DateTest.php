<?php
namespace Amiss\Test\Unit;

use Amiss\Type;
use Amiss\Sql;

/**
 * @group unit
 */
class DateTest extends \CustomTestCase
{
    public function setUp()
    {}
    
    public function testUnixPrepareForDb()
    {
        $handler = new \Amiss\Sql\Type\Date('U', 'UTC', 'UTC');
        $out = $handler->prepareValueForDb($this->createDate('2012-01-01 01:00:00', 'UTC'), null, array());
        $this->assertEquals(1325379600, $out);
    }
    
    public function testUnixHandleFromDb()
    {
        $handler = new \Amiss\Sql\Type\Date('U', 'UTC', 'UTC');
        $out = $handler->handleValueFromDb(1325379600, null, array(), array());
        
        $expected = $this->createDate('2012-01-01 01:00:00', 'UTC');
        $this->assertEquals($expected, $out);
    }
    
    public function testUnixHandleFromDbWithTimeZone()
    {
        $handler = new \Amiss\Sql\Type\Date('U', 'UTC', 'Australia/Melbourne');
        $out = $handler->handleValueFromDb(1, null, array(), array());
        
        $expected = $this->createDate('1970-01-01 10:00:01', 'Australia/Melbourne');
        $this->assertEquals($expected, $out);
    }
    
    public function testUnixHandleZeroFromDb()
    {
        $handler = new \Amiss\Sql\Type\Date('U', 'UTC', 'UTC');
        $out = $handler->handleValueFromDb(0, null, array(), array());
        $expected = $this->createDate('1970-01-01 00:00:00', 'UTC');
        $this->assertEquals($expected, $out);
    }

    /**
     * @dataProvider dataForHandleFromDbWithMultipleFormats
     */
    public function testDateTimeHandleFromDbWithMultipleFormats($format, $value, $expected)
    {
        // Handler order is deliberate - it ensures that the third one is picked up before the second when the
        // incoming value contains the extra values.
        $handler = new \Amiss\Sql\Type\Date(array('Y-m-d H:i:s', 'Y-m-d', 'Y-m-d H:i'), 'Australia/Melbourne', 'Australia/Melbourne');
        $out = $handler->handleValueFromDb($value, null, array(), array());
        $expected = \DateTime::createFromFormat($format, $value, new \DateTimeZone('Australia/Melbourne')); 
        $this->assertEquals($expected, $out);
    }

    public function dataForHandleFromDbWithMultipleFormats()
    {
        return array(
            array("Y-m-d H:i:s", "2012-03-02 11:10:09", "2012-03-02 11:10:09"),
            array("Y-m-d H:i", "2012-03-02 11:10", "2012-03-02 11:10:00"),
            array("Y-m-d", "2012-03-02", "2012-03-02 00:00:00"),
        );
    }

    /**
     * @dataProvider dataForHandleFromDbWithEmptyValueReturnsNull
     */
    public function testDateTimeHandleFromDbWithEmptyValueReturnsNull($value)
    {
        $handler = new \Amiss\Sql\Type\Date("datetime", 'Australia/Melbourne', 'Australia/Melbourne');
        $out = $handler->handleValueFromDb($value, null, array(), array());
        $this->assertNull($out);
    }

    public function dataForHandleFromDbWithEmptyValueReturnsNull()
    {
        return array(
            array(false),
            array(""),
            array(null),
        );
    }
    
    public function testDateTimeHandleFromDbWithMultipleFormatsFailsWhenNoFormatMatches()
    {
        $handler = new \Amiss\Sql\Type\Date(array('Y-m-d H:i:s'), 'Australia/Melbourne', 'Australia/Melbourne');
        $this->setExpectedException('UnexpectedValueException', 'Date \'2012-03-04\' could not be handled with any of the following formats: Y-m-d H:i:s');
        $out = $handler->handleValueFromDb('2012-03-04', null, array(), array());
    }

    public function testDateTimePrepareForDb()
    {
        $handler = new \Amiss\Sql\Type\Date('Y-m-d H:i:s', 'Australia/Melbourne', 'Australia/Melbourne');
        $out = $handler->prepareValueForDb($this->createDate('2012-01-01 12:00:00', 'Australia/Melbourne'), null, array());
        $this->assertEquals('2012-01-01 12:00:00', $out);
    }
    
    public function testDateTimePrepareForDbWithDifferentDbTimeZone()
    {
        $handler = new \Amiss\Sql\Type\Date('Y-m-d H:i:s', 'UTC', 'Australia/Melbourne');
        $out = $handler->prepareValueForDb($this->createDate('2012-01-01 12:00:00', 'Australia/Melbourne'), null, array());
        
        // Date should be converted to UTC before save
        $this->assertEquals('2012-01-01 01:00:00', $out);
    }
    
    private function createDate($date, $tz)
    {
        if ($tz && is_string($tz))
            $tz = new \DateTimeZone($tz);
        return \DateTime::createFromFormat('Y-m-d H:i:s', $date, $tz);
    }
}
