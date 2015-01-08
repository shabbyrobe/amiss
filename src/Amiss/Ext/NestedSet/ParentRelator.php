<?php
namespace Amiss\Ext\NestedSet;

use Amiss\Sql\Query\Criteria;
use Amiss\Meta;

class ParentRelator extends Relator
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
        if ($criteria)
            throw new \InvalidArgumentException("Can't use criteria with parent relator");
        
        $treeMeta = $this->nestedSetManager->getTreeMeta($source);
        
        $parentIdValue = $treeMeta->meta->getValue($source, $treeMeta->parentId);
        if ($parentIdValue)
            return $this->nestedSetManager->manager->getById($treeMeta->meta->class, $parentIdValue);
    }
}
