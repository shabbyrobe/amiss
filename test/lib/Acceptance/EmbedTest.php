<?php
namespace Amiss\Test\Acceptance;

use Amiss\Type;

/**
 * @group acceptance
 */
class EmbedTest extends \Amiss\Test\Helper\ModelDataTestCase
{
    public function setUp()
    {
        parent::setUp();
        
        if ($this->getEngine() == 'sqlite') {
            $this->db->exec("CREATE TABLE test_embed_one_parent(id INTEGER PRIMARY KEY AUTOINCREMENT, child TEXT)");
            $this->db->exec("CREATE TABLE test_embed_many_parent(id INTEGER PRIMARY KEY AUTOINCREMENT, children TEXT)");
        }
        elseif ($this->getEngine() == 'mysql') {
            $this->db->exec("CREATE TABLE test_embed_one_parent(id INT PRIMARY KEY AUTO_INCREMENT, child TEXT)");
            $this->db->exec("CREATE TABLE test_embed_many_parent(id INT PRIMARY KEY AUTO_INCREMENT, children TEXT)");   
        }
        // To test against either mysql or sqlite, we need to encode as well.
        $embed = new Type\Embed($this->mapper);
        $encoder = new Type\Encoder('serialize', 'unserialize', $embed);
        $this->mapper->addTypeHandler($encoder, 'embed');

        $this->mapper->objectNamespace = __NAMESPACE__;
    }

    public function testPrepareValueForDbWithOne()
    {
        $embed = new Type\Embed($this->mapper);

        $parent = new TestEmbedOneParent;
        $parent->child = new TestEmbedChild;
        $parent->child->foo = 'yep';
        $meta = $this->mapper->getMeta('TestEmbedOneParent');
        $field = $meta->getField('child');
        $result = $embed->prepareValueForDb($parent->child, $field);
        $expected = array('pants'=>'yep');

        $this->assertEquals($expected, $result);
    }

    public function testPrepareValueForDbWithMany()
    {
        $embed = new Type\Embed($this->mapper);

        $parent = new TestEmbedManyParent;
        $child = new TestEmbedChild;
        $child->foo = 'yep';
        $parent->children[] = $child;
        $child = new TestEmbedChild;
        $child->foo = 'yep2';
        $parent->children[] = $child;

        $meta = $this->mapper->getMeta('TestEmbedManyParent');
        $field = $meta->getField('children');
        $result = $embed->prepareValueForDb($parent->children, $field);
        $expected = array(array('pants'=>'yep'), array('pants'=>'yep2'));

        $this->assertEquals($expected, $result);
    }

    public function testHandleValueFromDbWithOne()
    {
        $embed = new Type\Embed($this->mapper);

        $parent = new TestEmbedOneParent;
        $expected = $parent->child = new TestEmbedChild;
        $parent->child->foo = 'yep';

        $meta = $this->mapper->getMeta('TestEmbedOneParent');
        $field = $meta->getField('child');
        $value = array('pants'=>'yep');
        $result = $embed->handleValueFromDb($value, $field, array());

        $this->assertEquals($expected, $result);
    }

    public function testHandleValueFromDbWithMany()
    {
        $embed = new Type\Embed($this->mapper);

        $parent = new TestEmbedManyParent;
        $child = new TestEmbedChild;
        $child->foo = 'yep';
        $parent->children[] = $child;
        $child = new TestEmbedChild;
        $child->foo = 'yep2';
        $parent->children[] = $child;

        $meta = $this->mapper->getMeta('TestEmbedManyParent');
        $field = $meta->getField('children');
        $value = array(array('pants'=>'yep'), array('pants'=>'yep2'));
        $result = $embed->handleValueFromDb($value, $field, array());

        $this->assertEquals($parent->children, $result);
    }

    public function testSaveEmbedOneToMySqlWithEncoder()
    {
        $parent = new TestEmbedOneParent();
        $parent->child = new TestEmbedChild();
        $parent->child->foo = array(1, 2, 3);
        $this->manager->save($parent);

        $result = $this->manager->getById('TestEmbedOneParent', 1);
        $this->assertEquals($parent, $result);
    }

    public function testSaveEmbedManyToMySqlWithEncoder()
    {
        $parent = new TestEmbedManyParent;
        $child = new TestEmbedChild;
        $child->foo = 'yep';
        $parent->children[] = $child;
        $child = new TestEmbedChild;
        $child->foo = 'yep2';
        $parent->children[] = $child;
        $this->manager->save($parent);

        $result = $this->manager->getById('TestEmbedManyParent', 1);
        $this->assertEquals($parent, $result);
    }
}

class TestEmbedOneParent
{
    /** :amiss = {"field": { "primary": true, "type": "autoinc" }}; */
    public $id;

    /** :amiss = {"field": {"type": {"id": "embed", "class": "TestEmbedChild"}}}; */
    public $child;
}

class TestEmbedManyParent
{
    /** :amiss = {"field": { "primary": true, "type": "autoinc" }}; */
    public $id;
    
    /** :amiss = {"field": {"type": {"id": "embed", "class": "TestEmbedChild", "many": true}}}; */
    public $children = array();
}

class TestEmbedChild
{
    /** :amiss = {"field": "pants"}; */
    public $foo;
}
