<?php
namespace Amiss\Test\Unit;

use Amiss\Type;
use Amiss\Sql;

/**
 * @group unit
 */
class DateTest extends \Amiss\Test\Helper\TestCase
{
    public function setUp()
    {}
    
    public function testUnixPrepareForDb()
    {
        $handler = new \Amiss\Sql\Type\Date(['formats'=>'U', 'appTimeZone'=>'UTC', 'dbTimeZone'=>'UTC']);
        $out = $handler->prepareValueForDb($this->createDate('2012-01-01 01:00:00', 'UTC'), array());
        $this->assertEquals(1325379600, $out);
    }
    
    public function testUnixHandleFromDb()
    {
        $handler = new \Amiss\Sql\Type\Date(['formats'=>'U', 'appTimeZone'=>'UTC', 'dbTimeZone'=>'UTC']);
        $out = $handler->handleValueFromDb(1325379600, array(), array());
        
        $expected = $this->createDate('2012-01-01 01:00:00', 'UTC');
        $this->assertEquals($expected, $out);
    }
    
    public function testUnixHandleFromDbWithTimeZone()
    {
        $handler = new \Amiss\Sql\Type\Date(['formats'=>'U', 'appTimeZone'=>'UTC', 'dbTimeZone'=>'Australia/Melbourne']);
        $out = $handler->handleValueFromDb(1, array(), array());
        
        $expected = $this->createDate('1970-01-01 10:00:01', 'Australia/Melbourne');
        $this->assertEquals($expected, $out);
    }
    
    public function testUnixHandleZeroFromDb()
    {
        $handler = new \Amiss\Sql\Type\Date(['formats'=>'U', 'appTimeZone'=>'UTC', 'dbTimeZone'=>'UTC']);
        $out = $handler->handleValueFromDb(0, array(), array());
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
        $handler = new \Amiss\Sql\Type\Date(['formats'=>['Y-m-d H:i:s', 'Y-m-d', 'Y-m-d H:i'], 'appTimeZone'=>'Australia/Melbourne', 'dbTimeZone'=>'Australia/Melbourne']);
        $out = $handler->handleValueFromDb($value, array(), array());
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
        $handler = new \Amiss\Sql\Type\Date(['formats'=>"datetime", 'appTimeZone'=>'Australia/Melbourne', 'dbTimeZone'=>'Australia/Melbourne']);
        $out = $handler->handleValueFromDb($value, array(), array());
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
        $handler = new \Amiss\Sql\Type\Date(['formats'=>['Y-m-d H:i:s'], 'appTimeZone'=>'Australia/Melbourne', 'dbTimeZone'=>'Australia/Melbourne']);
        $this->setExpectedException('UnexpectedValueException', 'Date \'2012-03-04\' could not be handled with any of the following formats: Y-m-d H:i:s');
        $out = $handler->handleValueFromDb('2012-03-04', array(), array());
    }

    public function testDateTimePrepareForDb()
    {
        $handler = new \Amiss\Sql\Type\Date(['formats'=>'Y-m-d H:i:s', 'appTimeZone'=>'Australia/Melbourne', 'dbTimeZone'=>'Australia/Melbourne']);
        $out = $handler->prepareValueForDb($this->createDate('2012-01-01 12:00:00', 'Australia/Melbourne'), array());
        $this->assertEquals('2012-01-01 12:00:00', $out);
    }
    
    public function testDateTimePrepareForDbWithDifferentDbTimeZone()
    {
        $handler = new \Amiss\Sql\Type\Date(['formats'=>'Y-m-d H:i:s', 'dbTimeZone'=>'UTC', 'appTimeZone'=>'Australia/Melbourne']);
        $out = $handler->prepareValueForDb($this->createDate('2012-01-01 12:00:00', 'Australia/Melbourne'), array());
        
        // Date should be converted to UTC before save
        $this->assertEquals('2012-01-01 01:00:00', $out);
    }
    
    private function createDate($date, $tz)
    {
        if ($tz && is_string($tz))
            $tz = new \DateTimeZone($tz);
        return \DateTime::createFromFormat('Y-m-d H:i:s', $date, $tz);
    }

    public function testDateTimeCustomClassPrepareForDb()
    {
        $class = __NAMESPACE__.'\PantsDateTime';
        $tz = new \DateTimeZone('UTC');
        $handler = new \Amiss\Sql\Type\Date(['formats'=>'Y-m-d H:i:s', 'appTimeZone'=>'UTC', 'dbTimeZone'=>'UTC', 'dateClass'=>$class]); 
        $out = $handler->prepareValueForDb(new PantsDateTime('2015-01-01', $tz), array());
        $this->assertEquals('2015-01-01 00:00:00', $out);
    }

    public function testDateTimeCustomClassHandleValueFromDb()
    {
        $class = __NAMESPACE__.'\PantsDateTime';
        $tz = new \DateTimeZone('UTC');
        $handler = new \Amiss\Sql\Type\Date(['formats'=>'Y-m-d H:i:s', 'appTimeZone'=>'UTC', 'dbTimeZone'=>'UTC', 'dateClass'=>$class]); 
        $out = $handler->handleValueFromDb('2015-01-01 00:00:00', [], array());
        $this->assertInstanceOf($class, $out);
    }

    public function testDateTimeCustomClassFailsWhenPassedRawDateTime()
    {
        $class = __NAMESPACE__.'\PantsDateTime';
        $tz = new \DateTimeZone('UTC');
        $handler = new \Amiss\Sql\Type\Date(['formats'=>'Y-m-d H:i:s', 'appTimeZone'=>'UTC', 'dbTimeZone'=>'UTC', 'dateClass'=>$class]); 

        $this->setExpectedException(
            'UnexpectedValueException',
            "Date value was invalid. Expected $class, found DateTime"
        );
        $out = $handler->prepareValueForDb(new \DateTime('2015-01-01', $tz), array());
    }

    public function testDateTimeCustomClassFailsWhenPassedString()
    {
        $class = __NAMESPACE__.'\PantsDateTime';
        $tz = new \DateTimeZone('UTC');
        $handler = new \Amiss\Sql\Type\Date(['formats'=>'Y-m-d H:i:s', 'appTimeZone'=>'UTC', 'dbTimeZone'=>'UTC', 'dateClass'=>$class]); 

        $this->setExpectedException(
            'UnexpectedValueException',
            "Date value was invalid. Expected $class, found string"
        );
        $out = $handler->prepareValueForDb('2015-01-01', array());
    }
}

class PantsDateTime extends \DateTime
{
    public static function createFromFormat($format, $time, $tz=null)
    {
        $dateTime = \DateTime::createFromFormat($format, $time, $tz);
        $dt = new static('@'.$dateTime->getTimeStamp(), new \DateTimeZone('UTC'));
        $dt->setTimeZone($dateTime->getTimeZone());
        return $dt;
    }
}

