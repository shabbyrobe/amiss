<?php
namespace Amiss\Test\Acceptance;

/**
 * Ensures objects with mapped field names (different from the property name)
 * work as expected
 *
 * @group acceptance
 * @group manager
 */
class MappedFieldNameTest extends \DataTestCase
{
    /**
     * @var Amiss\Sql\Manager
     */
    public $manager;

    /**
     * @var Amiss\Mapper
     */
    public $mapper;

    public function setUp()
    {
        parent::setUp();
        
        $this->db = $this->getConnector();
        
        $this->manager = \Amiss\Factory::createSqlManager($this->db);
        $this->mapper = $this->manager->mapper;
        
        $this->mapper->objectNamespace = __NAMESPACE__;

        $this->createRecordMemoryDb('MappedFieldNameLeft');
        $this->createRecordMemoryDb('MappedFieldNameAssoc');
        $this->createRecordMemoryDb('MappedFieldNameRight');
    }

    public function loadTestData()
    {
        $this->db->exec("INSERT INTO mapped_field_name_left(mapped_field_name_left_id, my_pants) VALUES(1, 'foo')");
        $this->db->exec("INSERT INTO mapped_field_name_left(mapped_field_name_left_id, my_pants) VALUES(2, 'bar')");
        $this->db->exec("INSERT INTO mapped_field_name_left(mapped_field_name_left_id, my_pants) VALUES(3, 'baz')");

        $this->db->exec("INSERT INTO mapped_field_name_right(mapped_field_name_right_id, my_trousers) VALUES(4, 'trou 1')");
        $this->db->exec("INSERT INTO mapped_field_name_right(mapped_field_name_right_id, my_trousers) VALUES(5, 'trou 2')");
        $this->db->exec("INSERT INTO mapped_field_name_right(mapped_field_name_right_id, my_trousers) VALUES(6, 'trou 3')");

        $this->db->exec("INSERT INTO mapped_field_name_assoc(mapped_field_name_assoc_id, left_id, right_id) VALUES(1, 1, 4)");
        $this->db->exec("INSERT INTO mapped_field_name_assoc(mapped_field_name_assoc_id, left_id, right_id) VALUES(2, 1, 5)");
        $this->db->exec("INSERT INTO mapped_field_name_assoc(mapped_field_name_assoc_id, left_id, right_id) VALUES(3, 2, 5)");
        $this->db->exec("INSERT INTO mapped_field_name_assoc(mapped_field_name_assoc_id, left_id, right_id) VALUES(4, 3, 6)");
    }

    public function testSaveNew()
    {
        $left = new MappedFieldNameLeft;
        $left->pants = 'test';
        $this->manager->save($left);
        $this->assertEquals(1, $left->id);
        $this->assertFalse(property_exists($left, 'mapped_field_name_left_id'));
    }

    public function testGetById()
    {
        $this->loadTestData();
        $obj = $this->manager->getById('MappedFieldNameLeft', 1);
        $this->assertEquals(1, $obj->id);
        $this->assertEquals('foo', $obj->pants);
    }

    public function testGetOneRelated()
    {
        $this->loadTestData();
        $obj = $this->manager->getById('MappedFieldNameAssoc', 1);
        $left = $this->manager->getRelated($obj, 'left');
        $this->assertEquals(1, $left->id);
        $this->assertEquals('foo', $left->pants);
    }

    public function testGetManyRelatedWithExplicitOn()
    {
        $this->loadTestData();
        $obj = $this->manager->getById('MappedFieldNameLeft', 1);
        $assocs = $this->manager->getRelated($obj, 'assocs');
        $this->assertCount(2, $assocs);
    }

    public function testGetManyRelatedWithInverseOn()
    {
        $this->loadTestData();
        $obj = $this->manager->getById('MappedFieldNameRight', 5);
        $assocs = $this->manager->getRelated($obj, 'assocs');
        $this->assertCount(2, $assocs);
    }
}

class MappedFieldNameLeft
{
    /**
     * @primary
     * @type autoinc
     * @field mapped_field_name_left_id
     */
    public $id;

    /**
     * @field my_pants
     */
    public $pants;

    /**
     * @has.many.of MappedFieldNameAssoc
     * @has.many.to leftId
     */
    public $assocs = array();

    /**
     * @has.assoc.of MappedFieldNameRight
     * @has.assoc.via MappedFieldNameAssoc
     */
    public $rights = array();
}

class MappedFieldNameAssoc
{
    /**
     * @primary
     * @type autoinc
     * @field mapped_field_name_assoc_id
     */
    public $id;

    /**
     * @field left_id
     * @type integer
     * @index
     */
    public $leftId;

    /**
     * @field right_id
     * @type integer
     * @index
     */
    public $rightId;

    /**
     * @has.one.of MappedFieldNameLeft
     * @has.one.from leftId
     */
    public $left;

    /**
     * @has.one.of MappedFieldNameRight
     * @has.one.from rightId
     */
    public $right;
}

class MappedFieldNameRight
{
    /**
     * @primary
     * @type autoinc
     * @field mapped_field_name_right_id
     */
    public $id;

    /**
     * @field my_trousers
     */
    public $trousers;

    /**
     * @has.many.of MappedFieldNameAssoc
     * @has.many.inverse right
     */
    public $assocs = array();

    /**
     * @has.assoc.of MappedFieldNameLeft
     * @has.assoc.via MappedFieldNameAssoc
     */
    public $lefts = array();
}
