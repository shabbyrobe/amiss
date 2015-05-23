<?php
namespace Amiss\Ext\NestedSet;

use Amiss\Sql\Query\Criteria;
use Amiss\Meta;

class TreeRelator extends Relator
{
    public $nestedSetManager;
    
    public function __construct(Manager $nestedSetManager)
    {
        $this->nestedSetManager = $nestedSetManager;
    }
    
    function getRelated(Meta $meta, $source, array $relation, Criteria $criteria=null)
    {
        if (!$source) {
            return $source;
        }

        $treeMeta = $this->nestedSetManager->getTreeMeta($source);
        $meta = $treeMeta->meta;

        $leftRights = [$meta->getValue($source, $treeMeta->leftId)=>$meta->getValue($source, $treeMeta->rightId)];

        $children = $this->fetchChildren($treeMeta, $criteria, $leftRights);

        if ($children) {
            $index = $this->buildTree($treeMeta, [$source], $children);
            return $index->nodes[$meta->getValue($source, $meta->primary[0])]->tree;
        }
    }

    function getRelatedForList(Meta $meta, $source, array $relation, Criteria $criteria=null)
    {
        if (!$source) {
            return $source;
        }

        $treeMeta = $this->nestedSetManager->getTreeMeta(current($source));
        
        index: {
            $leftRights = [];
            $sourceIndex = [];
            foreach ($source as $i) {
                $leftValue  = $meta->getValue($i, $treeMeta->leftId);
                $rightValue = $meta->getValue($i, $treeMeta->rightId);
                $id = $meta->getIndexValue($i);

                $sourceIndex[implode('|', $id)] = [$i, $id, $leftValue, $rightValue];
                $leftRights[$leftValue] = $rightValue;
            }
            ksort($leftRights);
        }

        reduce_lr: {
            $lastLeft = null;
            $lastRight = null;
            foreach ($leftRights as $l=>$r) {
                if ($lastLeft !== null && $l < $lastRight) {
                    unset($leftRights[$l]);
                }
                $lastLeft = $l;
                $lastRight = $r;
            }
        }

        query: {
            $children = $this->fetchChildren($treeMeta, $criteria, $leftRights);
        }

        if ($children) {
            $index = $this->buildTree($treeMeta, $source, $children);

            $out = [];
            foreach ($source as $in) {
                // primary is ensured to have one column in getTreeMeta
                $id = $meta->getValue($in, $meta->primary[0]);
                $out[] = $index->nodes[$id]->tree;
            }
            return $out;
        }
    }

    private function fetchChildren($treeMeta, $criteria, $leftRights)
    {
        $query = new \Amiss\Sql\Query\Select;
        $idx = 0;
        foreach ($leftRights as $l=>$r) {
            $query->where .= (!$idx++ ? "" : " OR ").
                "({".$treeMeta->leftId."} > ? AND {".$treeMeta->rightId."} < ?)";
            $query->params[] = $l;
            $query->params[] = $r;
        }

        if ($criteria) {
            if ($criteria->where) {
                list ($cWhere, $cParams) = $criteria->buildClause($meta);
                $query->params = array_merge($cParams, $query->params);
                $query->where .= ' AND ('.$cWhere.')';
            }
            $query->order = $criteria->order;
        }
        
        $query->stack = $criteria ? $criteria->stack : null;
        $children = $this->nestedSetManager->manager->getList($treeMeta->meta->class, $query);
        return $children;
    }

    private function buildTree($treeMeta, $parents, $children)
    {
        $meta = $treeMeta->meta;

        // primary is ensured to have one column in getTreeMeta
        $primaryField = $meta->primary[0];
        
        $index = (object)array(
            'nodes'=>[],
            'children'=>[],
            'parents'=>[],
            'info'=>[],
        );

        $objects = array_unique(array_merge($parents, $children), SORT_REGULAR);
        foreach ($objects as $idx=>$node) {
            $id = $meta->getValue($node, $primaryField);
            $parentId = $meta->getValue($node, $treeMeta->parentId);
            $index->parents[$id] = $parentId;
            $index->info[$idx] = [$id, $parentId];

            $index->nodes[$id] = $node;
            if (!isset($index->children[$parentId])) {
                $index->children[$parentId] = array();
            }
            $index->children[$parentId][] = $node;
        }

        foreach ($objects as $idx=>$node) {
            list ($id, $parentId) = $index->info[$idx];
            
            if (isset($index->children[$id])) {
                $meta->setValue($node, $treeMeta->treeRel, $index->children[$id]);
            }
            if (isset($index->nodes[$parentId])) {
                $meta->setValue($node, $treeMeta->parentRel, $index->nodes[$parentId]);
            }
        }

        return $index;
    }
}
