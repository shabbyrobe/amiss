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
        $handler = new \Amiss\Sql\Type\Date('U');
        $out = $handler->prepareValueForDb($this->createDate('2012-01-01 01:00:00', 'UTC'), null, []);
        $this->assertEquals(1325379600, $out);
    }
    
    public function testUnixHandleFromDb()
    {
        $handler = new \Amiss\Sql\Type\Date('U');
        $out = $handler->handleValueFromDb(1325379600, null, [], []);
        $expected = $this->createDate('2012-01-01 01:00:00', 'UTC');
        $this->assertEquals($expected, $out);
    }
    
    public function testUnixHandleFromDbWithTimeZone()
    {
        $handler = new \Amiss\Sql\Type\Date('U', new \DateTimeZone('Australia/Melbourne'));
        $out = $handler->handleValueFromDb(1, null, [], []);
        $expected = $this->createDate('1970-01-01 10:00:01', 'Australia/Melbourne');
        $this->assertEquals($expected, $out);
    }
    
    public function testUnixHandleZeroFromDb()
    {
        $handler = new \Amiss\Sql\Type\Date('U');
        $out = $handler->handleValueFromDb(0, null, [], []);
        $expected = $this->createDate('1970-01-01 00:00:00', 'UTC');
        $this->assertEquals($expected, $out);
    }
    
    public function testDateTimePrepareForDb()
    {
        $handler = new \Amiss\Sql\Type\Date('Y-m-d H:i:s', 'Australia/Melbourne');
        $out = $handler->prepareValueForDb($this->createDate('2012-01-01 12:00:00', 'Australia/Melbourne'), null, []);
        $this->assertEquals('2012-01-01 12:00:00', $out);
    }
    
    public function testDateTimeHandleFromDbWithTimeZone()
    {
        $handler = new \Amiss\Sql\Type\Date('Y-m-d H:i:s', 'Australia/Melbourne');
        $out = $handler->handleValueFromDb('2012-01-01 01:00:00', null, [], []);
        $expected = $this->createDate('2012-01-01 12:00:00', 'A');
        $this->assertEquals($expected, $out);
    }
    
    private function createDate($date, $tz=null)
    {
        if ($tz && is_string($tz))
            $tz = new \DateTimeZone($tz);
        return \DateTime::createFromFormat('Y-m-d H:i:s', $date, $tz ?: new \DateTimeZone(date_default_timezone_get()));
    }
}
