<?php
namespace Amiss\Test\Acceptance;

use Amiss\Sql\ActiveRecord;
use Amiss\Sql\Manager;
use Amiss\Sql\TableBuilder;
use Amiss\Test;

/**
 * @group acceptance
 * @group active
 */
class ActiveRecordEventTest extends \Amiss\Test\Helper\TestCase
{
    public function testUpdateEvents()
    {
        $events = ['beforeUpdate', 'afterUpdate'];
        $deps = Test\Factory::managerNoteModelCustom($this->makeClass($events));
        $class = $deps->classes['Test'];
        $class::setManager($deps->manager);
        $deps->connector->exec("INSERT INTO test(id) VALUES(1);");

        $record = $class::getById(1);
        $record->update();
        $this->assertEquals(['beforeUpdate', 'afterUpdate'], $record->events);
    }

    public function testInsertEvents()
    {
        $events = ['beforeInsert', 'afterInsert'];
        $deps = Test\Factory::managerNoteModelCustom($this->makeClass($events));
        $class = $deps->classes['Test'];
        $class::setManager($deps->manager);

        $record = new $class;
        $record->insert();
        $this->assertEquals(['beforeInsert', 'afterInsert'], $record->events);
    }

    public function testSaveUpdateEvents()
    {
        $events = ['beforeSave', 'beforeUpdate', 'afterUpdate', 'afterSave'];
        $deps = Test\Factory::managerNoteModelCustom($this->makeClass($events));
        $class = $deps->classes['Test'];
        $class::setManager($deps->manager);
        $deps->connector->exec("INSERT INTO test(id) VALUES(1);");

        $record = $class::getById(1);
        $record->save();
        $this->assertEquals($events, $record->events);
    }

    public function testSaveInsertEvents()
    {
        $events = ['beforeSave', 'beforeInsert', 'afterInsert', 'afterSave'];
        $deps = Test\Factory::managerNoteModelCustom($this->makeClass($events));
        $class = $deps->classes['Test'];
        $class::setManager($deps->manager);

        $record = new $class;
        $record->save();
        $this->assertEquals($events, $record->events);
    }

    public function testDeleteEvents()
    {
        $events = ['beforeDelete', 'afterDelete'];
        $deps = Test\Factory::managerNoteModelCustom($this->makeClass($events));
        $class = $deps->classes['Test'];
        $class::setManager($deps->manager);
        $deps->connector->exec("INSERT INTO test(id) VALUES(1);");

        $record = $class::getById(1);
        $record->delete();
        $this->assertEquals($events, $record->events);
    }

    private function makeClass($events)
    {
        $eventMethods = '';
        foreach ((array)$events as $event) {
            $eventMethods .= 'public function '.$event.'() { $this->events[] = __FUNCTION__; } '."\n";
        }
        $tpl = '
            /** :amiss = true; */
            class Test extends \Amiss\Sql\ActiveRecord {
                /** :amiss = {"field": { "primary": true, "type": "autoinc" }}; */
                public $id;
                public $events = [];
                '.$eventMethods.'
            }
        ';
        return $tpl;
    }
}
