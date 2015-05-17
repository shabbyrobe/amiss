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
        $this->manager = \Amiss\Factory::createSqlManager(new \PDOK\Connector('sqlite::memory:'));
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
    public function testCreateTableFailsWhenConnectorIsNotPDOKConnector()
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
    /** :amiss = {"field": { "primary": true, "type": "int" }}; */
    public $testCreateId;
    
    /** :amiss = {"field": {"type": "varchar(128)"}}; */
    public $foo1;
    
    /** :amiss = {"field": {"type": "varchar(128)"}}; */
    public $foo2;
    
    /** :amiss = {"field": {"type": "int unsigned not null"}}; */
    public $pants;
}
    
class TestCreateDefaultField
{
    /** :amiss = {"field": { "primary": true, "type": "autoinc" }}; */
    public $testCreateDefaultFieldId;
    
    /** :amiss = {"field": true}; */
    public $foo;
    
    /** :amiss = {"field": true}; */
    public $bar;
}

/**
 * :amiss = {"table": "bar"};
 */
class TestCreateWithIndexedSingleOnRelation
{
    /** :amiss = {"field": { "primary": true, "type": "autoinc" }}; */
    public $barId;
    
    /** :amiss = {"field": {"index": true}}; */
    public $fooId;
    
    /** :amiss = {"field": true}; */
    public $quack;
    
    /**
     * :amiss = {"has": {"type": "one", "of": "stdClass", "from": "fooId"}};
     */
    public $foo;
}

/**
 * :amiss = {
 *     "table": "bar",
 *     "indexes": {
 *         "myFoo": {"fields": ["myFooId", "myOtherFooId"]}
 *     }
 * };
 */
class TestCreateWithIndexedMultiOnRelation
{
    /** :amiss = {"field": { "primary": true, "type": "autoinc" }}; */
    public $barId;
    
    /** :amiss = {"field": true}; */
    public $myFooId;
    
    /** :amiss = {"field": true}; */
    public $myOtherFooId;
    
    /** :amiss = {"field": true}; */
    public $bar;
    
    /**
     * :amiss = {"has": {
     *     "type": "one",
     *     "of"  : "stdClass",
     *     "from": "myFoo"
     * }};
     */
    public $foo;
}
