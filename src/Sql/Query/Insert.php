<?php
namespace Amiss\Sql\Query;

use Amiss\Sql;
use Amiss\Exception;

class Insert extends Sql\Query
{
    // FIXME: this comes in with column names as keys rather than
    // property names.
    public $values;

    public function buildQuery($meta)
    {
        if (!$this->table) {
            throw new Exception("No table");
        }
        if (!$this->values) {
            throw new Exception("No values found for insert into {$this->table}");
        }

        $fields = $meta->getFields();

        // right, now that we have handled all the crazy arguments, let's insert!
        $columns = array();
        $count = count($this->values);
        $properties = [];

        $idx = 0;
        foreach ($this->values as $name=>$value) {
            $field = ($fieldSet = isset($fields[$name]) ? $fields[$name]['name'] : $name);

            if ($fieldSet) {
                $properties[$name] = $idx++;
            }
            $columns[] = '`'.str_replace('`', '', $field).'`';
        }

        $t = ($meta->schema ? "`{$meta->schema}`." : null)."`{$meta->table}`";
        $sql = "INSERT INTO $t(".implode(',', $columns).") ".
            "VALUES(?".($count > 1 ? str_repeat(",?", $count-1) : '').")";
        
        return [$sql, array_values($this->values), $properties];
    }
}
