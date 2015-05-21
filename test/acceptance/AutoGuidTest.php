<?php
namespace Amiss\Test\Acceptance;

use Amiss\Demo;

// this used to be a type handler but it never worked quite right.
// the test has been kept to sanity check that it's actually possible
// to do this through other means.
class AutoGuidTest extends \CustomMapperTestCase
{
    public function testInsertGeneratesGuid()
    {
        list ($this->manager, $ns) = $this->createDefaultNoteManager('
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
        $cls = "$ns\\AutoGuid";
        $o = new $cls;
        $this->manager->insert($o);
        $this->assertNotEmpty($o->guid);

        $o = $this->manager->get('AutoGuid');
        $this->assertNotEmpty($o->guid);
    }
}
