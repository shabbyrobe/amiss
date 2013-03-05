<?php
require __DIR__.'/../src/Amiss.php';
Amiss::register();

create_classes();

$manager = Amiss::createSqlManager(array(
	'host'=>'127.0.0.1',
	'user'=>'root',
	'password'=>'qwerty',
));

Amiss\Sql\ActiveRecord::setManager($manager);

$nestedSetManager = new NestedSetManager($manager);
$manager->relators['parents'] = new NestedSetParentRelator($manager);
$manager->relators['tree'] = new NestedSetTreeRelator($manager);

$regionMeta = $manager->getMeta('Region');
$region = $manager->getById('Region', 1);

$s = microtime(true);
//$tree = $manager->getRelated($region, 'childTree', array('order'=>array('regionShortCode'=>'DESC')));
$manager->assignRelated($region, 'childTree', array('order'=>array('regionShortCode'=>'DESC')));
var_dump(microtime(true)-$s);

$s = microtime(true);
$parents = $manager->getRelated($region, 'parents');
var_dump(microtime(true)-$s);

print_r($parents);
//print_r($region);

var_dump(memory_get_peak_usage());

function create_classes()
{
	class NestedSetManager
	{
		public function __construct($manager)
		{
			$this->manager = $manager;
		}
		
		/*
		public function renumber()
		{
			$sql = "SELECT ".$this->getIdField()." FROM ".$this->getTableName()." WHERE (".$this->getParentIdField()." IS NULL OR ".$this->getParentIdField()." = 0)";
			$treeIdField = $this->getTreeIdField();
			if ($treeIdField != null) {
				$sql .= "AND ".$treeIdField."=:treeId ";
			}
			$sql .= "LIMIT 1";
			$cmd = $this->getDbConnection()->createCommand($sql);
			if ($treeIdField != null) $cmd->bindValue(":treeId", $this->{$this->getTreeIdField()});
			
			$parentId = $cmd->queryScalar();
			
			if ($parentId <= 0)
				throw new Exception();
			
			$trans = $this->getDbConnection()->beginTransaction();
			try {
				$this->rebuildTree($parentId);
				$trans->commit();
			}
			catch (Exception $ex) {
				$trans->rollBack();
				throw $ex;
			}
		}
	
		protected function updateNode($id, $left, $right)
		{
			$sql = sprintf(
				"UPDATE %s SET %s = :left, %s = :right WHERE %s = :id", 
				$this->getTableName(), 
				$this->getTreeLeftField(), 
				$this->getTreeRightField(), 
				$this->getIdField()
			);
			
			$cmd = $this->getDbConnection()->createCommand($sql);
			$cmd->bindValue(":id", $id);
			$cmd->bindValue(":left", $left);
			$cmd->bindValue(":right", $right);
			
		    $result = $cmd->execute();
		}
		
		private function rebuildTree($node, $left=1)
		{
			$right = $left+1;
			$childIds = $this->getChildIds($node);
			
			foreach ($childIds as $child) {
				$right = $this->rebuildTree($child, $right);
			}
			if ($this->{$this->getIdField()} == null && $this->{$this->getParentIdField()} == $node) {
				$this->{$this->getTreeLeftField()} = $right;
				$this->{$this->getTreeRightField()} = $right + 1;
				$right += 2;
			}
			
			if ($this->{$this->getIdField()} != null && $this->{$this->getIdField()} == $node) {
				$this->{$this->getTreeLeftField()} = $left;
				$this->{$this->getTreeRightField()} = $right;
				//echo $this->{$this->getIdField()}."|".$node."|".$left;
			}
			else {
				$this->updateNode($node, $left, $right);
			}
			return $right+1;
		}
		
		*/
	}
	
	abstract class NestedSetRelator implements \Amiss\Sql\Relator
	{
		public $manager;
		
		public $leftType = 'treeleft';
		public $rightType = 'treeright';
		
		public function __construct($manager)
		{
			$this->manager = $manager;
		}
		
		protected function findTreeMeta($class, $relationName)
		{
			$meta = $this->manager->getMeta($class);
			list ($left, $right) = $this->findLeftRight($meta);
			
			if (!$meta->primary || isset($meta->primary[2]))
				throw new \UnexpectedValueException("Class $class must have a one-column primary for use with nested sets");
			
			return array(
				'meta'=>$meta,
				'leftField'=>$left,
				'rightField'=>$right,
				'relationName'=>$relationName,
			);
		}
		
		protected function getTreeMeta($object, $relationName)
		{
			$class = get_class($object);
			if (!isset($this->metaCache[$class])) {
				$meta = $this->findTreeMeta($class, $relationName);
				$this->metaCache[$class] = $meta;
			}
			return $this->metaCache[$class];
		}
		
		protected function findLeftRight($meta)
		{
			$leftField = null;
			$rightField = null;
			foreach ($meta->getFields() as $key=>$field) {
				if ($field['type'] == $this->leftType) {
					if ($leftField)
						throw new \UnexpectedValueException();
					$leftField = $field;
				}
				elseif ($field['type'] == $this->rightType) {
					if ($rightField)
						throw new \UnexpectedValueException();
					$rightField = $field;
				}
			}
			
			if (!$leftField || !$rightField)
				throw new \UnexpectedValueException();
			
			return array($leftField, $rightField);
		}
	}
	
	class NestedSetParentRelator extends NestedSetRelator
	{
		function getRelated($source, $relationName, $criteria=null)
		{
			if ($criteria)
				throw new \InvalidArgumentException("Can't use criteria with parent relator");
			
			$treeMeta = $this->getTreeMeta($source, $relationName);
			$meta = $treeMeta['meta'];
			$relation = $meta->relations[$relationName];
			
			$leftName = $treeMeta['leftField']['name'];
			$rightName = $treeMeta['rightField']['name'];
			$leftValue = $meta->getValue($source, $leftName);
			$rightValue = $meta->getValue($source, $rightName);
			
			$parents = $this->manager->getList($meta->class, array(
				'where'=>"{".$leftName."} < ? AND {".$rightName."} > ?",
				'params'=>array($leftValue, $rightValue),
				'order'=>array($leftName=>'desc'), 
			));
			
			if ($parents && isset($relation['includeRoot']) && !$relation['includeRoot'])
				array_pop($parents);
			
			return $parents;
		}
	}
	
	class NestedSetTreeRelator extends NestedSetRelator
	{
		protected function findTreeMeta($class, $relationName)
		{
			$treeMeta = parent::findTreeMeta($class, $relationName);
			
			$relation = $treeMeta['meta']->relations[$relationName];
			
			$treeMeta['parentId'] = isset($relation['parentId']) ? $relation['parentId'] : 'parentId';
			$treeMeta['parentRel'] = isset($relation['parentRel']) ? $relation['parentRel'] : 'parent';
			
			return $treeMeta;
		}
		
		function getRelated($source, $relationName, $criteria=null)
		{
			$treeMeta = $this->getTreeMeta($source, $relationName);
			$meta = $treeMeta['meta'];
			
			$relation = $meta->relations[$relationName];
			
			$leftName = $treeMeta['leftField']['name'];
			$rightName = $treeMeta['rightField']['name'];
			$leftValue = $meta->getValue($source, $leftName);
			$rightValue = $meta->getValue($source, $rightName);
			
        	$query = new \Amiss\Sql\Criteria\Select;
			$query->where = "{".$leftName."} > ? AND {".$rightName."} < ?";
			$query->params = array($leftValue, $rightValue);
			
	        if ($criteria) {
	        	if ($criteria->where) {
		            list ($cWhere, $cParams) = $criteria->buildClause($meta);
		            $query->params = array_merge($cParams, $query->params);
		            $query->where .= ' AND ('.$cWhere.')';
	        	}
	            $query->order = $criteria->order;
	        }
	        
			$children = $this->manager->getList($meta->class, $query);
			var_dump(count($children));
			if ($children)
				return $this->buildTree($treeMeta, $source, $children);
		}
		
		private function buildTree($treeMeta, $rootNode, $objects)
		{
			$meta = $treeMeta['meta'];
			
			// primary is ensured to have one column in getTreeMeta
			$primaryField = $meta->primary[0];
			
			$rootNodeId = $meta->getValue($rootNode, $primaryField);
			
			$index = (object)array(
				'nodes'=>array($rootNodeId=>$rootNode),
				'children'=>array(),
			);
			
			foreach ($objects as $node) {
	      		$id = $meta->getValue($node, $primaryField);
	      		$parentId = $meta->getValue($node, $treeMeta['parentId']);
	      		
	      		$index->nodes[$id] = $node;
	      		if (!isset($index->children[$parentId])) {
	      			$index->children[$parentId] = array();
	      		}
	      		$index->children[$parentId][] = $node;
			}
			
			foreach ($objects as $node) {
	      		$id = $meta->getValue($node, $primaryField);
	      		$parentId = $meta->getValue($node, $treeMeta['parentId']);
	      		
	      		if (isset($index->children[$id]))
		      		$meta->setValue($node, $treeMeta['relationName'], $index->children[$id]);
	      		
      			$meta->setValue($node, $treeMeta['parentRel'], $index->nodes[$parentId]);
			}
			
			return $index->children[$rootNodeId];
		}
	}
	
	/**
	 * @table region
	 */
	class Region extends \Amiss\Sql\ActiveRecord
	{
	    /**
	     * @primary
	     * @type autoinc
	     */
	    public $regionId;
	
	    /**
	     * @field
	     * @type tinyint(1) NULL
	     */
	    public $isRoot;
	
	    /**
	     * @field parentRegionId
	     * @type int(10) unsigned NULL
	     */
	    public $parentId;
	
	    /**
	     * @field
	     * @type int(10) unsigned
	     */
	    public $regionTypeId;
	
	    /**
	     * @field
	     * @type varchar(80)
	     */
	    public $regionName;
	
	    /**
	     * @field
	     * @type varchar(10)
	     */
	    public $regionShortCode;
	
	    /**
	     * @field
	     * @type varchar(100)
	     */
	    public $regionDisplayName;
	
	    /**
	     * @field
	     * @type treeleft
	     */
	    public $treeLeft;
	
	    /**
	     * @field
	     * @type treeright
	     */
	    public $treeRight;
	
	    /**
	     * @field
	     * @type int(10) unsigned NULL
	     */
	    public $level;
	
	    /**
	     * @field
	     * @type tinyint(1)
	     */
	    public $isPublic;
		
	    /**
	     * @has one of=Region; on[parentRegionId]=regionId
	     */
	    public $parent;
	    
	    /**
	     * @has parents includeRoot=0
	     */
	    public $parents;
	    
	    /**
	     * @has many of=Region; on[regionId]=parentRegionId
	     */
	    public $directChildren;
	    
	    /**
	     * @has tree
	     */
	    public $childTree;
	}
}

/*

	protected function moveRelatedChild($relation, $sequenceColumn, $from, $to, $groupBy=null)
    {
    	$relationData = $this->getRecordRelation($relation);
    	if ($relationData == null)
    		throw new Exception("no relation ".$relation);
    	if ($relationData[1][0] != self::HAS_MANY) {
    		throw new Exception("only works with has many");
    	}
    	$parentIdColumn = $relationData[1][2];
    	$finder = call_user_func(array($relationData[1][1], "finder"));
    	$childTable = $finder->getRecordTableInfo()->getTableName();
    	
    	$criteria = new TActiveRecordCriteria();
    	$criteria->Condition=$parentIdColumn.'=:id AND '.$sequenceColumn.'=:sequence';
    	if ($groupBy != null) {h
    		foreach ($groupBy as $k=>$v) {
    			self::appendCondition($criteria, self::C_AND, $k."=:".$k, array(":".$k => $v));
    		}
    	}
    	$pk = $this->getRecordTableInfo()->getPrimaryKeys();
    	$pk = $pk[0];
    	$parentId = $this->$pk;
    	$criteria->Parameters[':id'] = $parentId;
    	$criteria->Parameters[':sequence'] = $from;
    	$fromItem = $finder->find($criteria);
    	
    	$criteria->Parameters[':sequence'] = $to;
    	$toItem = $finder->find($criteria);
    	
    	$db = $this->getDbConnection();
    	$db->Active = true;
    	
    	if ($toItem == null || $fromItem == null) {
    		self::resequenceRelatedChildren($relation, $sequenceColumn, null, null, $groupBy);
    		throw new Exception("Sequence was out for parent ".$parentId);
    	}
    	
    	$tok = array("{sequenceColumn}" => $sequenceColumn, "{parentIdColumn}" => $parentIdColumn, "{childTable}" => $childTable);
    	if ($from > $to) {
    		// update where sequence >= to and sequence < from
    		$sql = "UPDATE {childTable} SET {sequenceColumn}={sequenceColumn}+1 WHERE {sequenceColumn}>=:to and {sequenceColumn}<:from AND {parentIdColumn}=:id";
    	}
		else {
			$sql = "UPDATE {childTable} SET {sequenceColumn}={sequenceColumn}-1 WHERE {sequenceColumn}>:from and {sequenceColumn}<=:to AND {parentIdColumn}=:id";
		}
		$grpToks = array();
    	$cnt = 0;
    	if ($groupBy) {
	    	foreach ($groupBy as $k=>$v) {
	    		$sql .= " AND ".$k."=:t_".$cnt;
	    		$grpToks[":t_".$cnt] = $v;
	    		$cnt++;
    		}
    	}
    	$cmd = $db->createCommand(strtr($sql, $tok));
    	$cmd->bindValue(":id", $parentId);
    	$cmd->bindValue(":to", $to);
    	$cmd->bindValue(":from", $from);
    	foreach ($grpToks as $k=>$v) $cmd->bindValue($k, $v);
    	$cmd->execute();
    	$fromItem->$sequenceColumn = $to;
    	$fromItem->save();
    }
    
    protected function resequenceRelatedChildren($relation, $sequenceColumn, $from=null, $to=null, $groupBy=null)
    {
    	$relation = $this->getRecordRelation($relation);
    	if ($relation[1][0] != self::HAS_MANY) {
    		throw new Exception("only works with has many");
    	}
    	
    	$parentIdColumn = $relation[1][2];
    	$finder = call_user_func(array($relation[1][1], "finder"));
    	$childTable = $finder->getRecordTableInfo()->getTableName();
    	
    	$pk = $this->getRecordTableInfo()->getPrimaryKeys();
    	$pk = $pk[0];
    	$parentId = $this->$pk;
    	
    	$criteria = new TActiveRecordCriteria;
    	$criteria->OrdersBy[$sequenceColumn] = 'asc';
    	$criteria->Condition = $parentIdColumn.'=:id';
    	$criteria->Parameters[':id'] = $parentId;
    	
    	if ($groupBy != null) {
    		foreach ($groupBy as $k=>$v) {
    			self::appendCondition($criteria, self::C_AND, $k."=:".$k, array(":".$k => $v));
    		}
    	}
    	
    	if ($from != null) {
    		$criteria->Condition .= " AND ".$sequenceColumn." >= :from";
    		$criteria->Parameters[':from'] = $from;
    	}
    	if ($to != null) {
    		$criteria->Condition .= " AND ".$sequenceColumn." <= :to";
    		$criteria->Parameters[':to'] = $to;
    	}
    	
    	$items = $finder->findAll($criteria);
    	$seq = $from == null ? 0 : $from;
    	foreach ($items as $item) {
    		$item->$sequenceColumn = $seq++;
    		$item->save();
    	}
    }

}



*/

