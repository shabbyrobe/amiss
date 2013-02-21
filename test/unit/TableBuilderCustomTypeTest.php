<?php
namespace Amiss\Test\Unit;

use Amiss\Sql\TableBuilder;

class TableBuilderCustomTypeTest extends \CustomTestCase
{
    public function setUp()
    {
        parent::setUp();
        
        $this->connector = new \TestConnector('mysql:xx');
        $this->mapper = \Amiss::createMapper();
        $this->manager = new \Amiss\Sql\Manager($this->connector, $this->mapper);
        $this->tableBuilder = new TableBuilder($this->manager, __NAMESPACE__.'\TestCreateWithCustomType');
    }
    
    /**
     * @covers Amiss\Sql\TableBuilder::buildFields
     * @group tablebuilder
     * @group unit
     */
    public function testCreateTableWithCustomTypeUsesRubbishValueWhenTypeHandlerNotRegistered()
    {
        $pattern = "
            CREATE TABLE `bar` (
                `testCreateId` INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT,
                `foo1` slappywag,
                `foo2` slappywag,
                `pants` int unsigned not null
            ) ENGINE=InnoDB
        ";
        $this->tableBuilder->createTable();
        $last = $this->connector->getLastCall();
        $this->assertLoose($pattern, $last[0]);
    }
    
    /**
     * @covers Amiss\Sql\TableBuilder::buildFields
     * @group tablebuilder
     * @group unit
     */
    public function testCreateTableWithCustomTypeUsesTypeHandler()
    {
        $this->mapper->addTypeHandler(new TestCreateWithCustomTypeTypeHandler, 'slappywag');
        
        $pattern = "
            CREATE TABLE `bar` (
                `testCreateId` INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT,
                `foo1` OH YEAH,
                `foo2` OH YEAH,
                `pants` int unsigned not null
            ) ENGINE=InnoDB
        ";
        $this->tableBuilder->createTable();
        
        $last = $this->connector->getLastCall();
        $this->assertLoose($pattern, $last[0]);
    }
}

/**
 * @table bar
 */
class TestCreateWithCustomType
{
    /**
     * @primary
     * @type autoinc
     */
    public $testCreateId;
    
    /**
     * @field
     * @type slappywag
     */
    public $foo1;
    
    /**
     * @field
     * @type slappywag
     */
    public $foo2;
    
    /**
     * @field
     * @type int unsigned not null
     */
    public $pants;
}

class TestCreateWithCustomTypeTypeHandler implements \Amiss\Type\Handler
{
    function prepareValueForDb($value, $object, array $fieldInfo)
    {
        return $value;
    }
    
    function handleValueFromDb($value, $object, array $fieldInfo, $row)
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
