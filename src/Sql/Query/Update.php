<?php
namespace Amiss\Sql\Query;

class Update extends Criteria
{
    public $set = array();

    public static function fromParamArgs(array $args, $class=null)
    {
        if (!$args) {
            throw new \InvalidArgumentException("Args missing for update");
        }

        $cnt = count($args);
        if ($cnt == 1) {
            return $args[0] instanceof self ? $args[0] : new Update($args[0]);
        }
        elseif ($cnt == 2 || $cnt == 3) {
            if (!is_array($args[0]) && !is_string($args[0])) {
                throw new \InvalidArgumentException("Set must be an array or string");
            }
            $query = new Update();
            $query->set = array_shift($args);
            $query->setParams($args);
            return $query;
        }
        else {
            throw new \InvalidArgumentException("Unknown args count $cnt");
        }
    }

    public function buildSet($meta, &$fidx=0)
    {
        $params = array();
        $clause = null;
        $properties = [];
        
        $fields = $meta ? $meta->fields : null;
        $named = $this->paramsAreNamed();
        
        if (is_string($this->set)) {
            $clause = $this->set;
        }
        else {
            $clause = [];
            foreach ($this->set as $name=>$value) {
                if (!is_string($name)) {
                    // this allows arrays of manual "set"s, i.e. array('foo=foo+10', 'bar=baz')
                    // TODO: integrate {property} substitution
                    $clause[] = $value;
                }
                else {
                    $field = ($fieldSet = isset($fields[$name]) ? $fields[$name]['name'] : $name);

                    if ($named) {
                        $param = ':zs_'.$fidx++;
                        $clause[] = '`'.$field.'`='.$param;
                        $params[$param] = $value;
                        if ($fieldSet) {
                            $properties[$name] = $param;
                        }
                    }
                    else {
                        $clause[] = '`'.$field.'`=?';
                        $params[] = $value;
                    }
                }
            }
            $clause = implode(', ', $clause);
        }
        return array($clause, $params, $properties);
    }
    
    public function buildQuery($meta, &$fidx=0)
    {
        $table = $this->table ?: $meta->table;

        list ($setClause,   $setParams,   $setProps)   = $this->buildSet($meta, $fidx);
        list ($whereClause, $whereParams, $whereProps) = $this->buildClause($meta, $fidx);

        $params = array_merge($setParams, $whereParams);
        if (count($params) != count($setParams) + count($whereParams)) {
            $intersection = array_intersect(array_keys($setParams), array_keys($whereParams));
            throw new Exception("Param overlap between set and where clause. Duplicated keys: ".implode(', ', $intersection));
        }
        
        if (!$whereClause) {
            throw new \InvalidArgumentException("No where clause specified for table update. Explicitly specify 1=1 as the clause if you meant to do this.");
        }
        
        $t = ($meta->schema ? "`{$meta->schema}`." : null)."`{$table}`";
        $sql = "UPDATE $t SET $setClause WHERE $whereClause";
        
        return array($sql, $params, $setProps, $whereProps);
    }
}
