<?php
namespace Amiss\Sql;

use Amiss\Meta;
use Amiss\Sql\Query\Criteria;

interface Relator
{
    /**
     * @param Criteria $criteria
     *    It is not required to handle all criteria, some may be impossible 
     *    or unreasonable. In this case, throw an InvalidArgumentException
     *    with a good descriptive message.
     */
    function getRelated(Meta $meta, $source, array $relation, Criteria $criteria=null);

    /**
     * @param Criteria $criteria
     *    It is not required to handle all criteria, some may be impossible 
     *    or unreasonable. In this case, throw an InvalidArgumentException
     *    with a good descriptive message.
     */
    function getRelatedForList(Meta $meta, $source, array $relation, Criteria $criteria=null);
}
