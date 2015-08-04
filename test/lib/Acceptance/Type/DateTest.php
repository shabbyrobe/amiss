<?php
namespace Amiss\Test\Acceptance\Type;

use Amiss\Test;

class DateTest extends \Amiss\Test\Helper\TestCase
{
    function testDateFromDB()
    {
        $d = Test\Factory::managerNoteModelCustom('
            /** :amiss = true; */
            class Pants {
                /** :amiss = {"field": {"primary": true, "type": "autoinc"}}; */
                public $id;

                /** :amiss = {"field": {"type": "date"}}; */
                public $date;
            }
        ');
        $d->connector->exec("INSERT INTO pants(`date`) VALUES('2014-01-01');");
        $obj = $d->manager->getById($d->classes['Pants'], 1);
        $this->assertInstanceOf(\DateTime::class, $obj->date);
        $this->assertEquals('2014-01-01T00:00:00+0000', $obj->date->format(\DateTime::ISO8601));
    }

    function testSelectDateForcedTime()
    {
        $d = Test\Factory::managerNoteModelCustom('
            /** :amiss = true; */
            class Pants {
                /** :amiss = {"field": {"primary": true, "type": "autoinc"}}; */
                public $id;

                /** :amiss = {"field": {"type": "date"}}; */
                public $date;
            }
        ');
        $d->connector->exec("INSERT INTO pants(`date`) VALUES('2014-01-01');");
        $date = new \DateTime('2014-01-01T12:00:00Z');
        $result = $d->manager->getList($d->classes['Pants'], '{date}>=:date', ['date'=>$date]);
        $this->assertCount(1, $result);
    }
}
