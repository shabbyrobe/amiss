<?php
namespace Amiss\Sql;

interface Relator
{
    function getRelated($source, $relationName, $criteria=null);

    function assignRelated($source, $result, $relation);
}
