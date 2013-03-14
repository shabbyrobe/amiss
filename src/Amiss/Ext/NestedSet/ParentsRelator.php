<?php
namespace Amiss\Ext\NestedSet;

class ParentsRelator implements \Amiss\Sql\Relator
{
    public $nestedSetManager;
    
    public function __construct(Manager $nestedSetManager)
    {
        $this->nestedSetManager = $nestedSetManager;
    }
    
    function getRelated($source, $relationName, $criteria=null)
    {
        if ($criteria)
            throw new \InvalidArgumentException("Can't use criteria with parents relator");
        
        $treeMeta = $this->nestedSetManager->getTreeMeta($source, $relationName);
        $meta = $treeMeta->meta;
        $relation = $meta->relations[$treeMeta->parentsRel];
        
        $leftValue = $meta->getValue($source, $treeMeta->leftId);
        $rightValue = $meta->getValue($source, $treeMeta->rightId);
        
        $parents = $this->nestedSetManager->manager->getList($meta->class, array(
            'where'=>"{".$treeMeta->leftId."} < ? AND {".$treeMeta->rightId."} > ?",
            'params'=>array($leftValue, $rightValue),
            'order'=>array($treeMeta->leftId=>'desc'),
        ));
        
        if ($parents && isset($relation['includeRoot']) && !$relation['includeRoot'])
            array_pop($parents);
        
        return $parents;
    }
}
