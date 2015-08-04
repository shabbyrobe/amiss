<?php
namespace Amiss\Test\Acceptance;

use Amiss\Demo;
use Amiss\Test;

/**
 * This method takes the bulk of its logic with getById
 * and createKeyCriteria. A simple test should suffice
 * unless that changes.
 */
class ExistsTest extends \Amiss\Test\Helper\TestCase
{
    /**
     * @group acceptance
     * @group manager 
     * @group exists
     */
    public function testExistsKeySingle()
    {
        $deps = Test\Factory::managerNoteModelCustom('
            /** :amiss = true; */
            class Pants {
                /** :amiss = {"field": {"index": {"key": true}}}; */
                public $slug;

                /** :amiss = {"field": true}; */
                public $name;
            }
        ');
        $class = $deps->classes['Pants'];
        $deps->manager->insertTable($class, ['slug'=>'yes', 'name'=>'Yep!']);
        $this->assertTrue ($deps->manager->exists($class, 'yes', 'slug'));
        $this->assertFalse($deps->manager->exists($class, 'nup', 'slug'));
    }
}
