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
        $this->manager = \Amiss\Factory::createSqlManager(new \Amiss\Sql\Connector('sqlite::memory:'));
    }
    
    /**
     * @group tablebuilder
     */
    public function testCreateDefaultTableSql()
    {
        $class = __NAMESPACE__.'\TestCreate';
         
        $pattern = "
            CREATE TABLE `test_create` (
                `testCreateId` int,
                `foo1` varchar(128),
                `foo2` varchar(128),
                `pants` int unsigned not null,
                PRIMARY KEY (`testCreateId`)
            );
        ";
        $sql = TableBuilder::createSQL('sqlite', $this->manager->mapper, $class);
        $this->assertLoose($pattern, $sql[0]);
    }
    
    /**
     * @group tablebuilder
     */
    public function testBuildCreateFieldsDefault()
    {
        $class = __NAMESPACE__.'\TestCreateDefaultField';
        
        $pattern = "
            CREATE TABLE `test_create_default_field` (
                `testCreateDefaultFieldId` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                `foo` STRING NULL,
                `bar` STRING NULL
            );
        ";
        $sql = TableBuilder::createSQL('sqlite', $this->manager->mapper, $class);
        $this->assertLoose($pattern, $sql[0]);
    }

    /**
     * @group tablebuilder
     */
    public function testCreateTableWithSingleOnRelation()
    {
        $class = __NAMESPACE__.'\TestCreateWithIndexedSingleOnRelation';
        
        $pattern = "
            CREATE TABLE `bar` (
                `barId` INTEGER NOT NULL AUTO_INCREMENT,
                `fooId` VARCHAR(255) NULL,
                `quack` VARCHAR(255) NULL,
                PRIMARY KEY (`barId`),
                KEY `fooId` (`fooId`)
            ) ENGINE=InnoDB;
        ";
        $sql = TableBuilder::createSQL('mysql', $this->manager->mapper, $class);
        $this->assertLoose($pattern, $sql[0]);
    }

    /**
     * @group tablebuilder
     */
    public function testCreateTableWithSingleOnRelationSkipsIndexesForSqlite()
    {
        $class = __NAMESPACE__.'\TestCreateWithIndexedSingleOnRelation';
        
        $patterns = [
            "CREATE TABLE `bar` (
                `barId` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                `fooId` STRING null,
                `quack` STRING null
            );",
            "CREATE INDEX `bar_fooId` ON `bar`(`fooId`);",
        ];
        $sql = TableBuilder::createSQL('sqlite', $this->manager->mapper, $class);
        $this->assertLoose($patterns[0], $sql[0]);
        $this->assertLoose($patterns[1], $sql[1]);
        $this->assertCount(2, $sql);
    }

    /**
     * @group tablebuilder
     */
    public function testCreateTableWithMultiOnRelation()
    {
        $class = __NAMESPACE__.'\TestCreateWithIndexedMultiOnRelation';
        
        $pattern = "
            CREATE TABLE `bar` (
                `barId` INTEGER NOT NULL AUTO_INCREMENT,
                `myFooId` VARCHAR(255) NULL,
                `myOtherFooId` VARCHAR(255) NULL,
                `bar` VARCHAR(255) NULL,
                PRIMARY KEY (`barId`),
                KEY `myFoo` (`myFooId`, `myOtherFooId`)
            )
        ";
        $sql = TableBuilder::createSQL('mysql', $this->manager->mapper, $class);
        $this->assertLoose($pattern, $sql[0]);
    }

    /**
     * @group tablebuilder
     * @expectedException Amiss\Exception
     */
    public function testCreateTableFailsWhenFieldsNotDefined()
    {
        TableBuilder::createSQL($this->manager->connector, $this->manager->mapper, __NAMESPACE__.'\TestNoFieldsCreate');
    }
    
    /**
     * @group tablebuilder
     */
    public function testCreateTableFailsWhenConnectorIsNotAmissConnector()
    {
        $this->manager->connector = new \PDO('sqlite::memory:');
        $this->setExpectedException('PHPUnit_Framework_Error');
        TableBuilder::create($this->manager->connector, $this->manager->mapper, __NAMESPACE__.'\TestNoFieldsCreate');
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
     * @index
     */
    public $fooId;
    
    /**
     * @field
     */
    public $quack;
    
    /**
     * @has.one.of stdClass
     * @has.one.from fooId
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
     * @index.myFoo 0
     */
    public $myFooId;
    
    /**
     * @field
     * @index.myFoo 1
     */
    public $myOtherFooId;
    
    /**
     * @field
     */
    public $bar;
    
    /**
     * @has.one.of stdClass
     * @has.one.from.myFoo
     */
    public $foo;
}
