<?php
namespace Amiss\Sql;

interface Relator
{
    function getRelated($source, $relationName, $criteria=null, $stack=[]);

    function assignRelated(array $source, array $result, $relation);
}
