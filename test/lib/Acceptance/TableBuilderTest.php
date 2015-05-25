<?php
namespace Amiss\Test\Acceptance;

use Amiss\Sql\TableBuilder;
use Amiss\Demo;
use Amiss\Test;

/**
 * @group tablebuilder
 * @group acceptance
 */
class TableBuilderTest extends \Amiss\Test\Helper\TestCase
{
    public function testCreateTable()
    {
        $deps = Test\Factory::managerNoteDefault();
        
        $deps->mapper->addTypeHandler(new \Amiss\Sql\Type\Autoinc, 'autoinc');
        $deps->mapper->objectNamespace = 'Amiss\Demo\Active';
        $deps->mapper->defaultTableNameTranslator = function($name) {
            return 'test_'.$name;
        };

        try {
            \Amiss\Demo\Active\DemoRecord::_reset();
            \Amiss\Demo\Active\DemoRecord::setManager($deps->manager);

            TableBuilder::create($deps->connector, $deps->mapper, 'Amiss\Demo\Active\EventRecord');
            
            $er = new Demo\Active\EventRecord();
            $er->name = 'foo bar';
            $er->slug = 'foobar';
            $er->save();
            
            $this->assertTrue(true);
        }
        finally {
            \Amiss\Demo\Active\DemoRecord::_reset();
        }
    }
}
