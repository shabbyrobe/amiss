<?php
namespace Amiss\Test\Unit;

use Amiss\Sql\TableBuilder;

/**
 * @group unit
 */
class TableBuilderCustomTypeTest extends \CustomTestCase
{
    public function setUp()
    {
        parent::setUp();
        
        $this->connector = new \TestConnector('mysql:xx');
        $this->mapper = \Amiss\Sql\Factory::createMapper();
        $this->manager = new \Amiss\Sql\Manager($this->connector, $this->mapper);
        $this->class = __NAMESPACE__.'\TestCreateWithCustomType';
    }
    
    /**
     * @covers Amiss\Sql\TableBuilder::buildFields
     * @group tablebuilder
     */
    public function testCreateTableWithCustomTypeUsesRubbishValueWhenTypeHandlerNotRegistered()
    {
        $pattern = "
            CREATE TABLE `bar` (
                `testCreateId` INTEGER NOT NULL AUTO_INCREMENT,
                `foo1` slappywag,
                `foo2` slappywag,
                `pants` int unsigned not null,
                PRIMARY KEY (`testCreateId`)
            ) ENGINE=InnoDB;
        ";
        $last = TableBuilder::createSQL($this->connector, $this->mapper, $this->class);
        $this->assertLoose($pattern, $last[0]);
    }
    
    /**
     * @covers Amiss\Sql\TableBuilder::buildFields
     * @group tablebuilder
     */
    public function testCreateTableWithCustomTypeUsesTypeHandler()
    {
        $this->mapper->addTypeHandler(new TestCreateWithCustomTypeTypeHandler, 'slappywag');
        
        $pattern = "
            CREATE TABLE `bar` (
                `testCreateId` INTEGER NOT NULL AUTO_INCREMENT,
                `foo1` OH YEAH,
                `foo2` OH YEAH,
                `pants` int unsigned not null,
                PRIMARY KEY (`testCreateId`)
            ) ENGINE=InnoDB;
        ";
        $last = TableBuilder::createSQL($this->connector, $this->mapper, $this->class);
        $this->assertLoose($pattern, $last[0]);
    }
}

/**
 * :amiss = {"table": "bar"};
 */
class TestCreateWithCustomType
{
    /** :amiss = {"field": { "primary": true, "type": "autoinc" }}; */
    public $testCreateId;
    
    /** :amiss = {"field": {"type": "slappywag"}}; */
    public $foo1;
    
    /** :amiss = {"field": {"type": "slappywag"}}; */
    public $foo2;
    
    /** :amiss = {"field": {"type": "int unsigned not null"}}; */
    public $pants;
}

class TestCreateWithCustomTypeTypeHandler implements \Amiss\Type\Handler
{
    function prepareValueForDb($value, array $fieldInfo)
    {
        return $value;
    }
    
    function handleValueFromDb($value, array $fieldInfo, $row)
    {
        return $value;
    }
    
    /**
     * It's ok to return nothing from this - the default column type
     * will be used.
     */
    function createColumnType($engine)
    {
        return "OH YEAH";
    }
}
