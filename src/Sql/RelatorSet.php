<?php
namespace Amiss\Sql;

class RelatorSet
{
    public function getOne($manager)
    {
        if (!isset($this->one))
            $this->one = new Relator\OneMany($manager);
        
        return $this->one;
    }
    
    public function getMany($manager)
    {
        return $this->getOne($manager);
    }
    
    public function getAssoc($manager)
    {
        return new Relator\Association($manager);
    }
}
