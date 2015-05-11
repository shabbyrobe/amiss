<?php
namespace Amiss\Test\Unit;

use Amiss\Sql\Manager;

/**
 * @group unit
 */
class ManagerCreateQueryFromArgsTest extends \CustomTestCase
{
    public function setUp()
    {
        parent::setUp();
        
        $this->manager = new Manager(
            array('dsn'=>'sqlite::memory:'),
            new \Amiss\Mapper\Note
        );
    }
    
    /**
     * @group manager
     * 
     * @covers Amiss\Sql\Manager::createQueryFromArgs
     * @covers Amiss\Sql\Manager::populateWhereAndParamsFromArgs
     */
    public function testHandlePositionalShorthandUnrolled()
    {
        $args = array('pants=? AND foo=?', ['pants', 'foo']);
        $criteria = $this->callProtected($this->manager, 'createQueryFromArgs', $args);
        
        $this->assertEquals(array('pants', 'foo'), $criteria->params);
        $this->assertEquals('pants=? AND foo=?', $criteria->where);
    }
    
    /**
     * @group manager
     * 
     * @covers Amiss\Sql\Manager::createQueryFromArgs
     * @covers Amiss\Sql\Manager::populateWhereAndParamsFromArgs
     */
    public function testHandlePositionalShorthandRolled()
    {
        $args = array('pants=? AND foo=?', array('pants', 'foo'));
        $criteria = $this->callProtected($this->manager, 'createQueryFromArgs', $args);
        
        $this->assertEquals(array('pants', 'foo'), $criteria->params);
        $this->assertEquals('pants=? AND foo=?', $criteria->where);
    }
    
    /**
     * @group manager
     * 
     * @covers Amiss\Sql\Manager::createQueryFromArgs
     * @covers Amiss\Sql\Manager::populateWhereAndParamsFromArgs
     */
    public function testHandlePositionalLongform()
    {
        $args = array(array('where'=>'pants=? AND foo=?', 'params'=>array('pants', 'foo')));
        $criteria = $this->callProtected($this->manager, 'createQueryFromArgs', $args);
        
        $this->assertEquals(array('pants', 'foo'), $criteria->params);
        $this->assertEquals('pants=? AND foo=?', $criteria->where);
    }
    
    /**
     * @group manager
     * 
     * @covers Amiss\Sql\Manager::createQueryFromArgs
     * @covers Amiss\Sql\Manager::populateWhereAndParamsFromArgs
     */
    public function testHandleNamedShorthand()
    {
        $args = array('pants=:pants AND foo=:foo', array(':pants'=>'pants', ':foo'=>'foo'));
        $criteria = $this->callProtected($this->manager, 'createQueryFromArgs', $args);
        
        $this->assertEquals(array(':pants'=>'pants', ':foo'=>'foo'), $criteria->params);
        $this->assertEquals('pants=:pants AND foo=:foo', $criteria->where);
    }
    
    /**
     * @group manager
     * 
     * @covers Amiss\Sql\Manager::createQueryFromArgs
     * @covers Amiss\Sql\Manager::populateWhereAndParamsFromArgs
     */
    public function testHandleNamedLongform()
    {
        $args = array(array('where'=>'pants=:pants AND foo=:foo', 'params'=>array(':pants'=>'pants', ':foo'=>'foo')));
        $criteria = $this->callProtected($this->manager, 'createQueryFromArgs', $args);
        
        $this->assertEquals(array(':pants'=>'pants', ':foo'=>'foo'), $criteria->params);
        $this->assertEquals('pants=:pants AND foo=:foo', $criteria->where);
    }
}
