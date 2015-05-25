<?php
namespace Amiss\Test\Acceptance;

class ManagerDeleteObjectTest extends \Amiss\Test\Helper\ModelDataTestCase
{
    public function setUp()
    {
        parent::setUp();
        
        $this->artist = $this->manager->get('Artist', 'artistId=?', array(1));
        if (!$this->artist)
            throw new \UnexpectedValueException("Unexpected test data");
    }

    /**
     * @group acceptance
     * @group manager
     */
    public function testDeleteById()
    {
        $this->manager->deleteById('Artist', 1);
        $this->assertEquals(0, $this->manager->count('Artist', 'name="Foobar"'));
        
        // sanity check: make sure we didn't delete everything!
        $this->assertGreaterThan(0, $this->manager->count('Artist'));
    }
    
    /**
     * @group acceptance
     * @group manager
     */
    public function testDeleteObject()
    {
        $this->manager->delete($this->artist);
        $this->assertEquals(0, $this->manager->count('Artist', 'name="Foobar"'));
        
        // sanity check: make sure we didn't delete everything!
        $this->assertGreaterThan(0, $this->manager->count('Artist'));
    }
    
    /**
     * @group acceptance
     * @group manager
     * @expectedException Amiss\Exception
     */
    public function testDeleteObjectWithoutPrimaryFails()
    {
        $mapper = new \Amiss\Test\Helper\TestMapper(array(
            'Amiss\Demo\Artist'=>new \Amiss\Meta('Artist', ['table'=>'artist']),
        ));

        $manager = new \Amiss\Sql\Manager($this->manager->connector, $mapper);
        $manager->delete($this->artist);
    }
}
