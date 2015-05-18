<?php
namespace Amiss\Ext\NestedSet;

class Manager
{
    /**
     * @var Amiss\Sql\Manager
     */
    public $manager;
    
    private $treeMeta = array();
    
    public static function createWithDefaults($manager)
    {
        $new = new static($manager);
        $manager->relators['parents'] = new ParentsRelator($new);
        $manager->relators['parent'] = new ParentRelator($new);
        $manager->relators['tree'] = new TreeRelator($new);
        return $new;
    }
    
    public function __construct(\Amiss\Sql\Manager $manager)
    {
        $this->manager = $manager;
    }
    
    public function getTreeMeta($class)
    {
        $class = is_object($class) ? get_class($class) : $class;
        
        if (!isset($this->treeMeta[$class])) {
            $this->treeMeta[$class] = $this->buildTreeMeta($class);
        }
        return $this->treeMeta[$class];
    }
    
    public function insert($object)
    {
        $return = $this->manager->insert($object);
        $this->renumber(get_class($object));
        return $return;
    }
    
    public function update($object)
    {
        $return = $this->manager->update($object);
        // TODO: determine if this is necessary
        // $this->renumber(get_class($object));
        return $return;
    }
    
    public function delete($object)
    {
        $return = $this->manager->delete($object);
        $this->renumber(get_class($object));
        return $return;
    }
    
    private function buildTreeMeta($class)
    {           
        $meta = $this->manager->getMeta($class);
        $ext = isset($meta->ext['nestedSet']) ? $meta->ext['nestedSet'] : null;
        
        if (!$ext) {
            throw new \InvalidArgumentException("$class did not define nestedSet extension");
        }
        if (!$meta->primary || isset($meta->primary[2])) {
            throw new \UnexpectedValueException("Class $class must have a one-column primary for use with nested sets");
        }
        
        $leftField   = isset($ext['leftId'])   ? $ext['leftId']   : 'treeLeft';
        $rightField  = isset($ext['rightId'])  ? $ext['rightId']  : 'treeRight';
        $parentField = isset($ext['parentId']) ? $ext['parentId'] : 'parentId';
        
        $fields = $meta->getFields();
        $treeMeta = (object)array(
            'meta'       => $meta,
            'leftId'     => isset($fields[$leftField]) ? $leftField : null,
            'rightId'    => isset($fields[$rightField]) ? $rightField : null,
            'parentId'   => isset($fields[$parentField]) ? $parentField : null,
            'parentRel'  => null,
            'parentsRel' => null,
            'treeRel'    => null,
        );
        
        foreach ($meta->relations as $id=>$rel) {
            if ($rel[0] == 'parent') {
                $treeMeta->parentRel = $id;
            } elseif ($rel[0] == 'parents') {
                $treeMeta->parentsRel = $id;
            } elseif ($rel[0] == 'tree') {
                $treeMeta->treeRel = $id;
            }
        }
        
        $missing = array();
        foreach ($treeMeta as $k=>$v) {
            if ($v === null) {
                $missing[] = $k;
            }
        }
        if ($missing) {
            throw new \UnexpectedValueException("Missing nestedSet keys: ".implode(", ", $missing));
        }
        return $treeMeta;
    }
    
    public function renumber($class)
    {
        $conn = clone $this->manager->connector;
        
        $treeMeta = $this->getTreeMeta($class);
        $meta = $treeMeta->meta;
        
        $conn->beginTransaction();
        if ($conn->engine == 'mysql') {
            $conn->exec("LOCK TABLES `{$meta->table}` WRITE");
        }
        
        $primaryName = $meta->getField($meta->primary[0])['name'];
        $parentIdFieldName = $meta->getField($treeMeta->parentId)['name'];
        $leftIdName = $meta->getField($treeMeta->leftId)['name'];
        $rightIdName = $meta->getField($treeMeta->rightId)['name'];
        
        $rootStmt = $conn->query("SELECT `{$primaryName}` FROM `{$meta->table}` WHERE (`{$parentIdFieldName}` IS NULL OR `{$parentIdFieldName}` = 0)");
        $rootId = $rootStmt->fetchAll(\PDO::FETCH_COLUMN, 0);
        if (!$rootId || count($rootId) > 1) {
            throw new \UnexpectedValueException("Could not find one and only one root id for class $class");
        }
        
        if ($conn->engine == 'mysql') {
            $conn->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        }
        
        $childIdStmt = $conn->prepare("SELECT `{$primaryName}` FROM `{$meta->table}` WHERE `{$parentIdFieldName}` = ?");
        $updateStmt = $conn->prepare("UPDATE `{$meta->table}` SET `{$leftIdName}`=?, `{$rightIdName}`=? WHERE `{$primaryName}`=?");
        
        $rebuildTree = function($nodeId, $left=1) use (&$rebuildTree, $treeMeta, $childIdStmt, $updateStmt) {
            $right = $left+1;
            
            $childIdStmt->execute(array($nodeId));
            $childIds = $childIdStmt->fetchAll(\PDO::FETCH_COLUMN, 0);
            
            foreach ($childIds as $childId) {
                $right = $rebuildTree($childId, $right);
            }
            
            $updateStmt->execute(array($left, $right, $nodeId));
            
            return $right+1;
        };
        
        $rebuildTree($rootId[0]);
        
        $conn->commit();
        
        if ($conn->engine == 'mysql') {
            $conn->exec("UNLOCK TABLES");
        }
    }
}
