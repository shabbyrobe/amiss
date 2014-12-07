<?php
namespace Amiss\Sql;

interface Relator
{
    function getRelated(RelatorContext $relatorContext=null, $source, $relationName, $criteria=null);
}
