<?php
namespace Amiss\Test\Acceptance\Type;

use Amiss\Test;
use Litipk\BigNumbers\Decimal;

class DecimalTest extends \Amiss\Test\Helper\TestCase
{
    function testDecimalFromDB()
    {
        $d = Test\Factory::managerNoteModelCustom('
            class Pants {
                /** :amiss = {"field": {"primary": true, "type": "autoinc"}}; */
                public $id;

                /** :amiss = {"field": {"type": "decimal"}}; */
                public $num;
            }
        ');
        $d->connector->exec("INSERT INTO pants(num) VALUES('1234.5678');");
        $obj = $d->manager->getById('Pants', 1);
        $this->assertInstanceOf(Decimal::class, $obj->num);
        $obj->num = $obj->num->add(Decimal::fromString("1.23456"));
        $this->assertEquals("1235.80236", $obj->num.'');
    }

    function testDecimalToDb()
    {
        $d = Test\Factory::managerNoteModelCustom('
            class Pants {
                /** :amiss = {"field": {"primary": true, "type": "autoinc"}}; */
                public $id;

                /** :amiss = {"field": {"type": "decimal"}}; */
                public $num;
            }
        ');
        $c = $d->ns.'\Pants';
        $obj = new $c;
        $obj->num = Decimal::fromString("1.23456");
        $d->manager->insert($obj);

        $out = $d->connector->query("SELECT num FROM pants")->fetchColumn(0);
        $this->assertEquals("1.23456", $out);
    }
}
