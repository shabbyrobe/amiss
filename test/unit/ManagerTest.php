<?php
namespace Amiss\Test\Unit;

/**
 * @group unit
 */
class ManagerTest extends \CustomTestCase
{
    public function setUp()
    {
        parent::setUp();
        
        $this->manager = new \Amiss\Sql\Manager(
            new \PDOK\Connector('sqlite::memory:'),
            new \Amiss\Mapper\Note
        );
    }

    /**
     * @group manager
     * @covers Amiss\Sql\Manager::getRelated
     */
    public function testGetRelatedFailsWhenRelationNameUnknown()
    {
        $source = new \stdClass;
        $this->setExpectedException("Amiss\Exception", "Unknown relation flobadoo on stdClass");
        $this->manager->getRelated($source, 'flobadoo');
    }

    /**
     * @group manager
     * @covers Amiss\Sql\Manager::getRelated
     */
    public function testGetRelatedFailsWhenRelatorUnknown()
    {
        $this->manager->mapper = $this->getMockBuilder("Amiss\Mapper")
            ->setMethods(array('getMeta'))
            ->getMockForAbstractClass()
        ;
        $meta = new \Amiss\Meta('stdClass', 'stdClass', array());
        $meta->relations = array(
            'a'=>array('wahey')
        );
        $this->manager->mapper->expects($this->any())->method('getMeta')->will($this->returnValue($meta));

        $source = new \stdClass;
        $this->setExpectedException("Amiss\Exception", "Relator wahey not found");
        $this->manager->getRelated($source, 'a');
    }
}
