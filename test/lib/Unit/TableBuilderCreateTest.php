<?php
namespace Amiss\Test\Unit;

use Amiss\Sql\TableBuilder;
use Amiss\Test;

/**
 * @group unit
 */
class TableBuilderCreateTest extends \Amiss\Test\Helper\TestCase
{
    public function setUp()
    {
        $this->deps = Test\Factory::managerNoteDefault();
        if ($this->deps->connector->engine != 'sqlite') {
            return $this->markTestSkipped();
        }
    }

    public function tearDown()
    {
        $this->deps = null;
        parent::tearDown();
    }
    
    /**
     * @group tablebuilder
     */
    public function testCreateDefaultTableSql()
    {
        $class = TestCreate::class;
         
        $pattern = "
            CREATE TABLE `test_create` (
                `testCreateId` int,
                `foo1` varchar(128),
                `foo2` varchar(128),
                `pants` int unsigned not null,
                PRIMARY KEY (`testCreateId`)
            );
        ";
        $sql = TableBuilder::createSQL('sqlite', $this->deps->mapper, $class);
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
        $sql = TableBuilder::createSQL('sqlite', $this->deps->mapper, $class);
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
        $sql = TableBuilder::createSQL('mysql', $this->deps->mapper, $class);
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
        $sql = TableBuilder::createSQL('sqlite', $this->deps->mapper, $class);
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
        $sql = TableBuilder::createSQL('mysql', $this->deps->mapper, $class);
        $this->assertLoose($pattern, $sql[0]);
    }

    /**
     * @group tablebuilder
     * @expectedException Amiss\Exception
     */
    public function testCreateTableFailsWhenFieldsNotDefined()
    {
        TableBuilder::createSQL($this->deps->connector, $this->deps->mapper, __NAMESPACE__.'\TestNoFieldsCreate');
    }
    
    /**
     * @group tablebuilder
     */
    public function testCreateTableFailsWhenConnectorIsNotPDOKConnector()
    {
        $this->deps->connector = new \PDO('sqlite::memory:');
        if (version_compare(PHP_VERSION, "7.0.0-dev") >= 0) {
            $this->setExpectedException('TypeException');
        } else {
            $this->setExpectedException('PHPUnit_Framework_Error');
        }
        TableBuilder::create($this->deps->connector, $this->deps->mapper, __NAMESPACE__.'\TestNoFieldsCreate');
    }
}

/** :amiss = true; */
class TestNoFieldsCreate
{
    
}

/** :amiss = true; */
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
    
/** :amiss = true; */
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
