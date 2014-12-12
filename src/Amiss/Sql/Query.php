<?php
namespace Amiss\Sql;

abstract class Query
{
    public $table;

    public function __construct(array $criteria=null)
    {
        if ($criteria) {
            $this->populate($criteria);
        }
    }
    
    public function populate(array $criteria)
    { 
        foreach ($criteria as $k=>$v) {
            $this->$k = $v;
        }
    }
}
