<?php
namespace Amiss\Sql;

use Amiss\Meta;
use Amiss\Sql\Query\Criteria;

interface Relator
{
    function getRelated(Meta $meta, $source, array $relation, Criteria $criteria=null);

    function getRelatedForList(Meta $meta, $source, array $relation, Criteria $criteria=null);
}
