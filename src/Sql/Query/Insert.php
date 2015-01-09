<?php
namespace Amiss\Sql\Query;

use Amiss\Sql;
use Amiss\Exception;

class Insert extends Sql\Query
{
    public $values;

    public function buildQuery()
    {
        if (!$this->table) {
            throw new Exception("No table");
        }
        if (!$this->values) {
            throw new Exception("No values found for insert into {$this->table}");
        }

        // right, now that we have handled all the crazy arguments, let's insert!
        $columns = array();
        $count = count($this->values);
        foreach ($this->values as $k=>$v) {
            $columns[] = '`'.str_replace('`', '', $k).'`';
        }

        $sql = "INSERT INTO {$this->table}(".implode(',', $columns).") ".
            "VALUES(?".($count > 1 ? str_repeat(",?", $count-1) : '').")";
        
        return [$sql, array_values($this->values)];
    }
}
