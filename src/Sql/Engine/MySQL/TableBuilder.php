<?php
namespace Amiss\Sql\Engine\MySQL;

use Amiss\Exception;

class TableBuilder extends \Amiss\Sql\TableBuilder
{
    public $defaultFieldType = 'VARCHAR(255) NULL';
    public $tableType = 'InnoDB';

    protected $engine = 'mysql';

    private function buildTableConstraints()
    {
        $idx = array();
        foreach ($this->meta->indexes as $k=>$details) {
            $fields = [];
            foreach ($details['fields'] as $p) {
                $fields[] = $this->meta->getField($p)['name'];
            }
            $colStr = '(`'.implode('`, `', $fields).'`)';
            if ($k == 'primary') {
                array_unshift($idx, "PRIMARY KEY $colStr");
            }
            else {
                $idx[] = ($details['key'] ? 'UNIQUE ' : '')."KEY `$k` $colStr";
            }
        }
        return $idx;
    }

    public function buildTableQueries()
    { 
        $table = '`'.str_replace('`', '', $this->meta->table).'`';

        $primary = $this->meta->primary;
        $fields = $this->buildFields();
        if (is_array($fields)) {
            $fields = implode(",\n  ", $fields);
        }
        
        $query = "CREATE TABLE $table (\n  ";
        $query .= $fields;
        
        $indexes = $this->buildTableConstraints();
        if ($indexes) {
            $query .= ",\n  ".implode(",\n  ", $indexes);
        }
        
        $query .= "\n)";
        $query .= ' ENGINE='.$this->tableType;
        $query .= ';';
        
        return [$query];
    }
}
