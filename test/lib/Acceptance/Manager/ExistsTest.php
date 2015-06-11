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
            class Pants {
                /** :amiss = {"field": {"index": {"key": true}}}; */
                public $slug;

                /** :amiss = {"field": true}; */
                public $name;
            }
        ');
        $deps->manager->insertTable('Pants', ['slug'=>'yes', 'name'=>'Yep!']);
        $this->assertTrue ($deps->manager->exists('Pants', 'yes', 'slug'));
        $this->assertFalse($deps->manager->exists('Pants', 'nup', 'slug'));
    }
}
