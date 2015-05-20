<?php
namespace Amiss\Ext\NestedSet;

use Amiss\Sql\Query\Criteria;
use Amiss\Meta;

class ParentsRelator extends Relator
{
    public $nestedSetManager;
    
    public function __construct(Manager $nestedSetManager)
    {
        $this->nestedSetManager = $nestedSetManager;
    }
    
    function getRelatedForList(Meta $meta, $source, array $relation, Criteria $criteria=null)
    {
        throw new \Exception('not implemented');
    }
    
    function getRelated(Meta $meta, $source, array $relation, Criteria $criteria=null)
    {
        if ($criteria) {
            throw new \InvalidArgumentException("Can't use criteria with parents relator");
        }
        
        $relationName = $relation[0];
        $treeMeta = $this->nestedSetManager->getTreeMeta($source, $relationName);
        $meta = $treeMeta->meta;
        $relation = $meta->relations[$treeMeta->parentsRel];
        
        $leftValue = $meta->getValue($source, $treeMeta->leftId);
        $rightValue = $meta->getValue($source, $treeMeta->rightId);
        
        $query = new \Amiss\Sql\Query\Select;

        $query->stack = $criteria ? $criteria->stack : null;
        $query->where = "{".$treeMeta->leftId."} < ? AND {".$treeMeta->rightId."} > ?";
        $query->params = array($leftValue, $rightValue);
        $query->order = array($treeMeta->leftId=>'desc');

        $parents = $this->nestedSetManager->manager->getList($meta->class, $query);

        if ($parents && isset($relation['includeRoot']) && !$relation['includeRoot']) {
            array_pop($parents);
        }
        
        return $parents;
    }
}
