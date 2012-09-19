<?php

namespace Amiss\Test\Unit;

use Amiss\TableBuilder;

class TableBuilderCustomEmptyColumnTypeTest extends \CustomTestCase
{
    public function setUp()
    {
        \Amiss\Active\Record::_reset();
        $this->connector = new \TestConnector('mysql:xx');
        $this->mapper = new \Amiss\Mapper\Note;
        $this->manager = new \Amiss\Manager($this->connector, $this->mapper);
        \Amiss\Active\Record::setManager($this->manager);
        $this->tableBuilder = new TableBuilder($this->manager, __NAMESPACE__.'\TestCreateCustomTypeWithEmptyColumnTypeRecord');
    }
    
    /**
     * @covers Amiss\TableBuilder::buildFields
     * @group tablebuilder
     * @group unit
     */
    public function testCreateTableWithCustomTypeUsesTypeHandler()
    {
        $this->mapper->addTypeHandler(new RecordCreateCustomTypeWithEmptyColumnTypeHandler, 'int');
        
        $pattern = "
            CREATE TABLE `bar` (
                `id` INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT,
                `foo1` int
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
class TestCreateCustomTypeWithEmptyColumnTypeRecord extends \Amiss\Active\Record
{
    /**
     * @primary
     * @type autoinc
     */
    public $id;
    
    /**
     * @field
     * @type int
     */
    public $foo1;
}

class RecordCreateCustomTypeWithEmptyColumnTypeHandler implements \Amiss\Type\Handler
{
    function prepareValueForDb($value, $object, array $fieldInfo)
    {
        return $value;
    }
    
    function handleValueFromDb($value, $object, array $fieldInfo, $row)
    {
        return $value;
    }
    
    function createColumnType($engine)
    {}
}
