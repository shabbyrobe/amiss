<?php
namespace Amiss\Test\Acceptance;

use Amiss\Sql\TableBuilder,
    Amiss\Demo
;

class TableBuilderTest extends \DataTestCase
{
    /**
     * @group tablebuilder
     * @group acceptance
     */
    public function testCreateTable()
    {
        $db = $this->getConnector();
        
        $manager = new \Amiss\Sql\Manager($db, new \Amiss\Mapper\Note);
        $manager->mapper->addTypeHandler(new \Amiss\Sql\Type\Autoinc, 'autoinc');
        $manager->mapper->objectNamespace = 'Amiss\Demo\Active';
        $manager->mapper->defaultTableNameTranslator = function($name) {
            return 'test_'.$name;
        };
        
        \Amiss\Sql\ActiveRecord::_reset();
        \Amiss\Sql\ActiveRecord::setManager($manager);
        
        $tableBuilder = new TableBuilder($manager, 'Amiss\Demo\Active\EventRecord');
        $tableBuilder->createTable();
        
        $er = new Demo\Active\EventRecord();
        $er->name = 'foo bar';
        $er->slug = 'foobar';
        $er->save();
        
        $this->assertTrue(true);
    }
}
