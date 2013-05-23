<?php
namespace Amiss\Test\Unit;

use Amiss\Sql\TableBuilder;

/**
 * @group unit
 */
class TableBuilderCreateTest extends \DataTestCase
{
    public function setUp()
    {
        if ($this->getConnector()->engine != 'sqlite')
            return $this->markTestSkipped();
        
        parent::setUp();
        $this->manager = \Amiss::createSqlManager(new \Amiss\Sql\Connector('sqlite::memory:'));
    }
    
    /**
     * @group tablebuilder
     */
    public function testCreateDefaultTableSql()
    {
        $tableBuilder = new TableBuilder($this->manager, __NAMESPACE__.'\TestCreate');
         
        $pattern = "
            CREATE TABLE `test_create` (
                `testCreateId` int,
                `foo1` varchar(128),
                `foo2` varchar(128),
                `pants` int unsigned not null
            )
        ";
        
        $this->manager->connector = $this->getMock('Amiss\Sql\Connector', array('exec'), array('sqlite::memory:'));
        $this->manager->connector->expects($this->once())->method('exec')
            ->with($this->matchesLoose($pattern));
        
        $tableBuilder->createTable();
    }
    
    /**
     * @group tablebuilder
     */
    public function testBuildCreateFieldsDefault()
    {
        $tableBuilder = new TableBuilder($this->manager, __NAMESPACE__.'\TestCreateDefaultField');
        
        $pattern = "
            CREATE TABLE `test_create_default_field` (
                `testCreateDefaultFieldId` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                `foo` STRING null,
                `bar` STRING null
            )
        ";
        $this->manager->connector = $this->getMock('Amiss\Sql\Connector', array('exec'), array('sqlite::memory:'));
        $this->manager->connector->expects($this->once())->method('exec')
            ->with($this->matchesLoose($pattern));
        
        $tableBuilder->createTable();
    }

    /**
     * @group tablebuilder
     */
    public function testCreateTableWithSingleOnRelation()
    {
        $tableBuilder = new TableBuilder($this->manager, __NAMESPACE__.'\TestCreateWithIndexedSingleOnRelation');
        
        $pattern = "
            CREATE TABLE `bar` (
                `barId` INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT,
                `fooId` varchar(255) null,
                `quack` varchar(255) null,
                KEY `idx_foo` (`fooId`)
            )
        ";
        $this->manager->connector = $this->getMock('Amiss\Sql\Connector', array('exec'), array('mysql:xx'));
        $this->manager->connector->expects($this->once())->method('exec')
            ->with($this->matchesLoose($pattern));
        
        $tableBuilder->createTable();
    }

    /**
     * @group tablebuilder
     */
    public function testCreateTableWithSingleOnRelationSkipsIndexesForSqlite()
    {
        $tableBuilder = new TableBuilder($this->manager, __NAMESPACE__.'\TestCreateWithIndexedSingleOnRelation');
        
        $pattern = "
            CREATE TABLE `bar` (
                `barId` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                `fooId` STRING null,
                `quack` STRING null
            )
        ";
        $this->manager->connector = $this->getMock('Amiss\Sql\Connector', array('exec'), array('sqlite::memory:'));
        $this->manager->connector->expects($this->once())->method('exec')
            ->with($this->matchesLoose($pattern));
        
        $tableBuilder->createTable();
    }

    /**
     * @group tablebuilder
     */
    public function testCreateTableWithMultiOnRelation()
    {
        $tableBuilder = new TableBuilder($this->manager, __NAMESPACE__.'\TestCreateWithIndexedMultiOnRelation');
        
        $pattern = "
            CREATE TABLE `bar` (
                `barId` INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT,
                `myFooId` varchar(255) null,
                `myOtherFooId` varchar(255) null,
                `bar` varchar(255) null,
                KEY `idx_foo` (`myFooId`, `myOtherFooId`)
            )
        ";
        $this->manager->connector = $this->getMock('Amiss\Sql\Connector', array('exec'), array('mysql:xx'));
        $this->manager->connector->expects($this->once())->method('exec')
            ->with($this->matchesLoose($pattern));
        
        $tableBuilder->createTable();
    }

    /**
     * @group tablebuilder
     * @expectedException Amiss\Exception
     */
    public function testCreateTableFailsWhenFieldsNotDefined()
    {
        $tableBuilder = new TableBuilder($this->manager, __NAMESPACE__.'\TestNoFieldsCreate');
        $tableBuilder->createTable();
    }
    
    /**
     * @group tablebuilder
     * @expectedException Amiss\Exception
     */
    public function testCreateTableFailsWhenConnectorIsNotAmissConnector()
    {
        $this->manager->connector = new \PDO('sqlite::memory:');
        $tableBuilder = new TableBuilder($this->manager, __NAMESPACE__.'\TestNoFieldsCreate');
        $tableBuilder->createTable();
    }
}

class TestNoFieldsCreate
{
    
}

class TestCreate
{
    /**
     * @primary
     * @type int
     */
    public $testCreateId;
    
    /**
     * @field
     * @type varchar(128)
     */
    public $foo1;
    
    /**
     * @field
     * @type varchar(128)
     */
    public $foo2;
    
    /**
     * @field
     * @type int unsigned not null
     */
    public $pants;
}
    
class TestCreateDefaultField
{
    /**
     * @primary
     * @type autoinc
     */
    public $testCreateDefaultFieldId;
    
    /**
     * @field
     */
    public $foo;
    
    /**
     * @field
     */
    public $bar;
}

/**
 * @table bar
 */
class TestCreateWithIndexedSingleOnRelation
{
    /**
     * @primary
     * @type autoinc
     */
    public $barId;
    
    /**
     * @field
     */
    public $fooId;
    
    /**
     * @field
     */
    public $quack;
    
    /**
     * @has.one.of stdClass
     * @has.one.on fooId
     */
    public $foo;
}

/**
 * @table bar
 */
class TestCreateWithIndexedMultiOnRelation
{
    /**
     * @primary
     * @type autoinc
     */
    public $barId;
    
    /**
     * @field
     */
    public $myFooId;
    
    /**
     * @field
     */
    public $myOtherFooId;
    
    /**
     * @field
     */
    public $bar;
    
    /**
     * @has.one.of stdClass
     * @has.one.on.myFooId fooId
     * @has.one.on.myOtherFooId otherFooId
     */
    public $foo;
}
