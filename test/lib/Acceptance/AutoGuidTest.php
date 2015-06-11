<?php
namespace Amiss\Test\Acceptance;

use Amiss\Test;

// this used to be a type handler but it never worked quite right.
// the test has been kept to sanity check that it's actually possible
// to do this through other means.
class AutoGuidTest extends \Amiss\Test\Helper\TestCase
{
    public function testInsertGeneratesGuid()
    {
        $d = Test\Factory::managerNoteModelCustom('
            /**
             * :amiss = {
             *     "on": {
             *         "beforeInsert": ["generateGuid"]
             *     },
             *     "primary": "id"
             * };
             */
            class AutoGuid {
                /** :amiss = {"field": {"type": "autoinc"}}; */
                public $id;
                
                /** :amiss = {"field": true}; */
                public $guid;

                public function generateGuid() {
                    $this->guid = \Amiss\Functions::guid();
                }
            }
        ');
        $cls = "{$d->ns}\\AutoGuid";
        $o = new $cls;
        $d->manager->insert($o);
        $this->assertNotEmpty($o->guid);

        $o = $d->manager->get('AutoGuid');
        $this->assertNotEmpty($o->guid);
    }
}
