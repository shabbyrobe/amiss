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
            new \Amiss\Sql\Connector('sqlite::memory:'),
            new \Amiss\Mapper\Note
        );
    }

    /**
     * @group manager
     * 
     * @covers Amiss\Sql\Manager::keyValue
     */
    public function testKeyValueWith2Tuples()
    {
        $input = array(
            array('a', 'b'),
            array('c', 'd'),
        );
        $result = $this->manager->keyValue($input);
        $expected = array(
            'a'=>'b',
            'c'=>'d'
        );
        $this->assertEquals($expected, $result);
    }

    /**
     * @group manager
     * 
     * @covers Amiss\Sql\Manager::keyValue
     */
    public function testKeyValueWith2TupleKeyOverwriting()
    {
        $input = array(
            array('a', 'b'),
            array('a', 'd'),
        );
        $result = $this->manager->keyValue($input);
        $expected = array(
            'a'=>'d'
        );
        $this->assertEquals($expected, $result);
    }

    /**
     * @group manager
     * 
     * @covers Amiss\Sql\Manager::keyValue
     */
    public function testKeyValueFromObjectsWithKeyValueProperties()
    {
        $input = array(
            (object)array('a'=>'1', 'c'=>'2'),
            (object)array('a'=>'3', 'c'=>'4'),
        );
        $result = $this->manager->keyValue($input, 'a', 'c');
        $expected = array(
            '1'=>'2',
            '3'=>'4',
        );
        $this->assertEquals($expected, $result);
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

    public function testPopulateSelectQueryFromArrayArgs()
    {
        $params = [
            'where'=>'foo',
            'params'=>['a', 'b'],
            'forUpdate'=>true,
            'order'=>'order!',
            'page'=>'1',
            'limit'=>'2',
            'offset'=>'3',
        ];
        $query = $this->callProtected($this->manager, 'createQueryFromArgs', [$params]);
        $this->assertTrue($query instanceof \Amiss\Sql\Criteria\Select);
        $this->assertEquals($params['where'], $query->where);
        $this->assertEquals($params['params'], $query->params);
        $this->assertEquals($params['forUpdate'], $query->forUpdate);
        $this->assertEquals($params['order'], $query->order);
        $this->assertEquals($params['page'], $query->page);
        $this->assertEquals($params['limit'], $query->limit);
        $this->assertEquals($params['offset'], $query->offset);
    }
}
