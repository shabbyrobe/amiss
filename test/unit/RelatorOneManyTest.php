<?php

namespace Amiss\Tests\Unit;

class RelatorOneManyTest extends \CustomTestCase
{
	public function setUp()
	{
		$this->mapper = new \TestMapper;
		
		$this->db = $this->getMockBuilder('Amiss\Connector')
			->disableOriginalConstructor()
			->getMock()
		;
		$this->manager = $this->getMockBuilder('Amiss\Manager')
			->setConstructorArgs(array($this->db, $this->mapper))
			->setMethods(array('getList', 'get', 'getRelated'))
			->getMock()
		;
		$this->relator = new \Amiss\Relator\OneMany($this->manager);
		
		if (!class_exists('DummyParent')) {
			eval('class DummyParent { public $id; public $parentId; } class DummyChild { public $id; }');
		}
	}

	/**
	 * @dataProvider dataForGetOneToOne
	 */
	public function testGetOneToOne($index, $data)
	{
		$this->createSinglePrimaryMeta($data);
		
		$source = new \DummyChild;
		$source->childId = 1;
		$source->childParentId = 2;
		
		list ($class, $query) = $this->captureRelatedQuery($source, 'parent');
		$this->assertEquals('DummyParent', $class);
		$this->assertEquals('`parent_id` IN(:r_parent_id)', $query->where);
		$this->assertEquals(array('r_parent_id'=>array(2)), $query->params);
	}
	
	function dataForGetOneToOne()
	{
		return array(
			array(1, array('childOn'=>'childParentId')),
			array(2, array('childOn'=>array('childParentId'=>'parentId'))),
		);
	}
	
	/**
	 * @dataProvider dataForGetOneToMany
	 */
	public function testGetOneToMany($data)
	{
		$this->createSinglePrimaryMeta($data);
		$source = new \DummyParent;
		$source->parentId = 1;
		
		list ($class, $query) = $this->captureRelatedQuery($source, 'children');
		$this->assertEquals('DummyChild', $class);
		$this->assertEquals('`child_parent_id` IN(:r_child_parent_id)', $query->where);
		$this->assertEquals(array('r_child_parent_id'=>array(1)), $query->params);
	}
	
	function dataForGetOneToMany()
	{
		return array(
			array(array('childParentField'=>'parentId', 'parentOn'=>'parentId')),
			array(array('parentOn'=>array('parentId'=>'childParentId'))),
		);
	}
	
	protected function captureRelatedQuery($source, $relation)
	{
		$capture = null;
		
		$this->manager->expects($this->any())->method('getList')->will($this->returnCallback(
			function () use (&$capture) {
				$capture = func_get_args();
			}
		));
		
		$this->relator->getRelated($source, $relation);
		
		return $capture;
	}
	
	protected function createSinglePrimaryMeta($data)
	{
		$defaults = array(
			'parentOn'=>null,
			'childOn'=>null,
			
			'childIdField'=>'childId',
			'childIdColumn'=>'child_id',
			
			'childParentField'=>'childParentId',
			'childParentColumn'=>'child_parent_id',
			
			'parentIdField'=>'parentId',
			'parentIdColumn'=>'parent_id',
		);
		
		$data = array_merge($defaults, $data);
		
		$source = new \DummyParent();
		$metaIndex = array();
		
		$this->mapper->meta['DummyChild'] = new \Amiss\Meta('DummyChild', 'child', array(
			'primary'=>array($data['childIdField']),
			'fields'=>array(
				$data['childIdField']     => array('name'=>$data['childIdColumn']    ?: $data['childIdField']),
				$data['childParentField'] => array('name'=>$data['childParentColumn'] ?: $data['childParentField']),
			),
			'relations'=>array(
				'parent'=>array('one', 'of'=>'DummyParent', 'on'=>$data['childOn'])
			),
		));
		$this->mapper->meta['DummyParent'] = new \Amiss\Meta('DummyParent', 'parent', array(
			'primary'=>array($data['parentIdField']),
			'fields'=>array(
				$data['parentIdField']=>array('name'=>$data['parentIdColumn'] ?: $data['parentIdField']),
			),
			'relations'=>array(
				'children'=>array('many', 'of'=>'DummyChild', 'on'=>$data['parentOn'])
			),
		));
	}
}
