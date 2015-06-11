<?php
namespace Amiss\Test\Acceptance\NestedSet;

use Amiss\Test;

abstract class TestCase extends \Amiss\Test\Helper\TestCase
{
    protected static function managerNestedSetArrays($map)
    {
        $deps = Test\Factory::managerArraysModelCustom($map);
        $deps->nsManager = \Amiss\Ext\NestedSet\Manager::createWithDefaults($deps->manager);
        return $deps;
    }

    protected static function managerNestedSetNote($classes)
    {
        $deps = Test\Factory::managerNoteModelCustom($classes);
        $deps->nsManager = \Amiss\Ext\NestedSet\Manager::createWithDefaults($deps->manager);
        return $deps;
    }

    function idTree($parent, $tree, $idProp='id', $treeProp='tree')
    {
        $r = function($c) use (&$r, $idProp, $treeProp) {
            if (!$c) { return true; }
            $ids = [];
            foreach ($c as $child) {
                $ids[$child->$idProp] = isset($child->$treeProp) ? $r($child->$treeProp) : true;
            }
            return $ids;
        };
        return [$parent->$idProp=>$r($tree)];
    }
}

