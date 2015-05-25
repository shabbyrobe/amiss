<?php
namespace Amiss\Test\Acceptance;

use Amiss\Demo;

/**
 * This method takes the bulk of its logic with getById
 * and createKeyCriteria. A simple test should suffice
 * unless that changes.
 */
class ExistsTest extends \Amiss\Test\Helper\CustomMapperTestCase
{
    /**
     * @group acceptance
     * @group manager 
     * @group exists
     */
    public function testExistsKeySingle()
    {
        list ($manager, $ns) = $this->createDefaultNoteManager('
            class Pants {
                /** :amiss = {"field": {"index": {"key": true}}}; */
                public $slug;

                /** :amiss = {"field": true}; */
                public $name;
            }
        ');
        $manager->insertTable('Pants', ['slug'=>'yes', 'name'=>'Yep!']);
        $this->assertTrue ($manager->exists('Pants', 'yes', 'slug'));
        $this->assertFalse($manager->exists('Pants', 'nup', 'slug'));
    }
}
