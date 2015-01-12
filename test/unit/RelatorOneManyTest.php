<?php
namespace Amiss\Tests\Unit;

/**
 * @group unit
 */
class RelatorOneManyTest extends \CustomTestCase
{
    public function setUp()
    {
        parent::setUp();
        
        $this->mapper = new \TestMapper;
        
        $this->db = $this->getMockBuilder('PDOK\Connector')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this->manager = $this->getMockBuilder('Amiss\Sql\Manager')
            ->setConstructorArgs(array($this->db, $this->mapper))
            ->setMethods(array('getList', 'get', 'getRelated'))
            ->getMock()
        ;
        $this->relator = new \Amiss\Sql\Relator\OneMany($this->manager);
        
        if (!class_exists('DummyParent')) {
            eval('
                class DummyParent { public $id; public $parentId; }
                class DummyChild { public $id; }
            ');
        }
    }

    public function testGetOneToOne()
    {
        $this->createSinglePrimaryMeta();
        $meta = $this->mapper->getMeta('DummyChild');
        
        $source = new \DummyChild;
        $source->childId = 1;
        $source->childParentId = 2;

        list ($class, $query) = $this->captureRelatedQuery($meta, $source, $meta->relations['parent']);
        $this->assertEquals('DummyParent', $class);
        $this->assertEquals('`parent_id` IN(:r_parent_id)', $query->where);
        $this->assertEquals(array('r_parent_id'=>array(2)), $query->params);
    }
    
    public function testGetOneToMany()
    {
        $this->createSinglePrimaryMeta();
        $meta = $this->mapper->getMeta('DummyParent');
        $source = new \DummyParent;
        $source->parentId = 1;
        
        list ($class, $query) = $this->captureRelatedQuery($meta, $source, $meta->relations['children']);
        $this->assertEquals('DummyChild', $class);
        $this->assertEquals('`child_parent_id` IN(:r_child_parent_id)', $query->where);
        $this->assertEquals(array('r_child_parent_id'=>array(1)), $query->params);
    }

    protected function captureRelatedQuery($meta, $source, $relation)
    {
        $capture = null;

        $this->manager->expects($this->any())->method('getList')->will($this->returnCallback(
            function () use (&$capture) {
                $capture = func_get_args();
            }
        ));

        $this->relator->getRelated($meta, $source, $relation);
        
        return $capture;
    }
    
    protected function createSinglePrimaryMeta()
    {
        $metaIndex = array();
        
        $this->mapper->meta['DummyChild'] = new \Amiss\Meta('DummyChild', 'child', array(
            'primary'=>array('childId'),
            'fields'=>array(
                'childId'=>array('name'=>'child_id'),
                'childParentId'=>array('name'=>'child_parent_id'),
            ),
            'indexes'=>array(
                'childParentId'=>array('fields'=>array('childParentId')),
            ),
            'relations'=>array(
                'parent'=>array('one', 'of'=>'DummyParent', 'from'=>'childParentId')
            ),
        ));
        $this->mapper->meta['DummyParent'] = new \Amiss\Meta('DummyParent', 'parent', array(
            'primary'=>array('parentId'),
            'fields'=>array(
                'parentId'=>array('name'=>'parent_id'),
            ),
            'relations'=>array(
                'children'=>array('many', 'of'=>'DummyChild', 'to'=>'childParentId'),
            ),
        ));
    }
}
