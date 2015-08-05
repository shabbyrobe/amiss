<?php
namespace Amiss\Sql\Engine\SQLite;

use Amiss\Exception;

class TableBuilder extends \Amiss\Sql\TableBuilder
{
    public $fieldType = 'STRING NULL';

    protected $engine = 'sqlite';

    private function buildTableConstraintQueries($table)
    {
        $queries = array();
        
        foreach ($this->meta->indexes as $k=>$details) {
            if ($k == 'primary') {
                continue;
            }
            $fields = [];
            foreach ($details['fields'] as $p) {
                $fields[] = $this->meta->fields[$p]['name'];
            }
            $colStr = '`'.implode('`, `', $fields).'`';

            $indexName = trim($table, '`')."_$k";
            $queries[] = "CREATE ".($details['key'] ? 'UNIQUE ' : '')."INDEX "
                ."`$indexName` ON $table($colStr);";
        }
        return $queries;
    }

    public function buildTableQueries()
    {
        $table = '`'.str_replace('`', '', $this->meta->table).'`';

        $fields = $this->buildFields();
        $foundPrimaries = [];
        foreach ($fields as $id=>$f) {
            // filthy hack to support AUTOINC + sqlite's bodgy autoincrement syntax
            if (preg_match('/\bprimary\s+key\b/i', $f)) {
                $foundPrimaries[] = $id;
            }
        }

        if ($foundPrimaries) {
            if ($diff = array_diff($foundPrimaries, $this->meta->primary)) {
                throw new \Exception('primary key properties extracted ('.implode(', ', $diff).'), but did not exist in meta');
            }
            if (array_diff($this->meta->primary, $foundPrimaries)) {
                throw new \Exception();
            }
        }

        if (is_array($fields)) {
            $fields = implode(",\n  ", $fields);
        }
        
        $query = "CREATE TABLE $table (\n  ";
        $query .= $fields;
        if (!$foundPrimaries && $this->meta->primary) {
            $priFields = [];
            foreach ($this->meta->primary as $p) {
                $priFields[] = $this->meta->fields[$p]['name'];
            }
            $query .= ",\n  PRIMARY KEY (`".implode('`, `', $priFields)."`)";
        }

        $query .= "\n);";

        return array_merge([$query], $this->buildTableConstraintQueries($table));
    }
}
