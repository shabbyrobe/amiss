<?php
namespace Amiss\Sql\Engine\SQLite;

use Amiss\Exception;

class TableBuilder extends \Amiss\Sql\TableBuilder
{
    public $defaultFieldType = 'STRING NULL';

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
                $metaField = $this->meta->getField($p);
                if (!$metaField) {
                    throw new \UnexpectedValueException("Unknown field '$p' in index '$k' for class {$this->meta->class}");
                }
                $fields[] = $this->meta->getField($p)['name'];
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
            if (array_diff($foundPrimaries, $this->meta->primary)) {
                throw new \Exception();
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
                $priFields[] = $this->meta->getField($p)['name'];
            }
            $query .= ",\n  PRIMARY KEY (`".implode('`, `', $priFields)."`)";
        }

        $query .= "\n);";

        return array_merge([$query], $this->buildTableConstraintQueries($table));
    }
}
