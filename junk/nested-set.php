<?php
require __DIR__.'/../src/Amiss.php';
Amiss::register();

create_classes();

$manager = Amiss::createSqlManager(array(
    'host'=>'127.0.0.1',
    'db'=>'test',
    'user'=>'root',
    'password'=>'qwerty',
));

$meta = $manager->getMeta('Region');
$nestedSetManager = Amiss\Ext\NestedSet\Manager::createWithDefaults($manager);

Amiss\Sql\ActiveRecord::setManager($manager);
Amiss\Ext\NestedSet\ActiveRecord::setNestedSetManager($nestedSetManager);

$region = Region::getById(1);
$q = new Region();
$q->parentId = 1;
$q->regionTypeId = 1;
$q->regionName = 'Boogers';
$q->regionDisplayName = 'Boogers';
$q->regionShortCode = 'yep';
$q->isPublic = true;
$q->insert();

var_dump(memory_get_usage());
var_dump(memory_get_peak_usage());

function create_classes()
{
    /**
     * @table region
     * @ext.nestedSet
     */
    class Region extends \Amiss\Ext\NestedSet\ActiveRecord
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
         */
        public $treeLeft;
    
        /**
         * @field
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
         * @has parent
         */
        public $parent;
        
        /**
         * @has.parents.includeRoot 0
         */
        public $parents;
        
        /**
         * @has.many.of Region
         * @has.many.on.regionId parentRegionId
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

