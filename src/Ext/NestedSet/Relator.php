<?php
namespace Amiss\Ext\NestedSet;

abstract class Relator implements \Amiss\Sql\Relator
{
    function assignRelated(array $source, array $result, $relation)
    {
        foreach ($result as $idx=>$item) {
            if (!isset($relation['setter'])) {
                $source[$idx]->{$relation['name']} = $item;
            } else {
                call_user_func(array($source[$idx], $relation['setter']), $item);
            }
        }
    }
}
