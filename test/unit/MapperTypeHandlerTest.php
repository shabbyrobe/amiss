<?php
namespace Amiss\Test\Acceptance;

use Amiss\Demo;

/**
 * @group unit
 */
class MapperTypeHandlerTest extends \CustomTestCase
{
    public function setUp()
    {
        parent::setUp();
        
        $this->mapper = $this->getMockBuilder('Amiss\Mapper\Base')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass()
        ;
    }
    
    /**
     * @group mapper
     * @covers Amiss\Mapper\Base::addTypeHandler
     */
    public function testAddTypeHandler()
    {
        $handler = new \TestTypeHandler();
        
        $this->assertFalse(isset($this->mapper->typeHandlers['foo']));
        
        $this->mapper->addTypeHandler(new \TestTypeHandler(), 'foo');
        $handler2 = $this->mapper->typeHandlers['foo'];
        
        $this->assertEquals($handler, $handler2);
    }
    
    /**
     * @group mapper
     * @covers Amiss\Mapper\Base::addTypeHandler
     */
    public function testAddTypeHandlerToManyTypes()
    {
        $handler = new \TestTypeHandler();
        
        $this->assertFalse(isset($this->mapper->typeHandlers['foo']));
        $this->assertFalse(isset($this->mapper->typeHandlers['bar']));
        
        $this->mapper->addTypeHandler(new \TestTypeHandler(), array('foo', 'bar'));
        
        $this->assertEquals($handler, $this->mapper->typeHandlers['foo']);
        $this->assertEquals($handler, $this->mapper->typeHandlers['bar']);
    }
}
