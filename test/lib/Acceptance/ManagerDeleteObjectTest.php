<?php
namespace Amiss\Test\Acceptance;

use Amiss\Test;

/**
 * @group acceptance
 * @group manager
 */
class ManagerDeleteObjectTest extends \Amiss\Test\Helper\TestCase
{
    public function setUp()
    {
        $this->deps = Test\Factory::managerModelDemo();
        $this->manager = $this->deps->manager;
        
        $this->artist = $this->deps->manager->get('Artist', 'artistId=?', array(1));
        if (!$this->artist) {
            throw new \UnexpectedValueException("Unexpected test data");
        }
    }

    public function tearDown()
    {
        $this->manager = null;
        $this->deps = null;
        parent::tearDown();
    }

    public function testDeleteById()
    {
        $this->manager->deleteById('Artist', 1);
        $this->assertEquals(0, $this->manager->count('Artist', 'name="Foobar"'));
        
        // sanity check: make sure we didn't delete everything!
        $this->assertGreaterThan(0, $this->manager->count('Artist'));
    }
    
    public function testDeleteObject()
    {
        $this->manager->delete($this->artist);
        $this->assertEquals(0, $this->manager->count('Artist', 'name="Foobar"'));
        
        // sanity check: make sure we didn't delete everything!
        $this->assertGreaterThan(0, $this->manager->count('Artist'));
    }

    public function testDeleteObjectWithoutPrimaryFails()
    {
        $mapper = new \Amiss\Test\Helper\TestMapper(array(
            'Amiss\Demo\Artist'=>new \Amiss\Meta('Artist', ['table'=>'artist']),
        ));

        $manager = new \Amiss\Sql\Manager($this->manager->connector, $mapper);
        $this->setExpectedException(\Amiss\Exception::class);
        $manager->delete($this->artist);
    }
}
