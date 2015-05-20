<?php
namespace Amiss\Test\Unit;

use Amiss\Sql\TableBuilder;

/**
 * @group unit
 */
class TableBuilderCustomEmptyColumnTypeTest extends \CustomTestCase
{
    public function setUp()
    {
        parent::setUp();
        
        \Amiss\Sql\ActiveRecord::_reset();
        $this->connector = new \TestConnector('mysql:xx');
        $this->mapper = \Amiss\Sql\Factory::createMapper(array());
        $this->manager = new \Amiss\Sql\Manager($this->connector, $this->mapper);
        \Amiss\Sql\ActiveRecord::setManager($this->manager);
        $this->class = __NAMESPACE__.'\TestCreateCustomTypeWithEmptyColumnTypeRecord';
    }
    
    /**
     * @covers Amiss\Sql\TableBuilder::buildFields
     * @group tablebuilder
     */
    public function testCreateTableWithCustomTypeUsesTypeHandler()
    {
        $this->mapper->addTypeHandler(new RecordCreateCustomTypeWithEmptyColumnTypeHandler, 'int');
        
        $pattern = "
            CREATE TABLE `bar` (
                `id` INTEGER NOT NULL AUTO_INCREMENT,
                `foo1` int,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB;
        ";
        $last = TableBuilder::createSQL($this->connector, $this->mapper, $this->class);
        $this->assertLoose($pattern, $last[0]);
    }
}

/**
 * :amiss = {"table": "bar"};
 */
class TestCreateCustomTypeWithEmptyColumnTypeRecord extends \Amiss\Sql\ActiveRecord
{
    /** :amiss = {"field": { "primary": true, "type": "autoinc" }}; */
    public $id;
    
    /** :amiss = {"field": {"type": "int"}}; */
    public $foo1;
}

class RecordCreateCustomTypeWithEmptyColumnTypeHandler implements \Amiss\Type\Handler
{
    function prepareValueForDb($value, array $fieldInfo)
    {
        return $value;
    }
    
    function handleValueFromDb($value, array $fieldInfo, $row)
    {
        return $value;
    }
    
    function createColumnType($engine, array $fieldInfo)
    {}
}
