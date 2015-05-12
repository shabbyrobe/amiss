<?php
namespace Amiss\Sql\Query;

class Update extends Criteria
{
    public $set = array();

    public static function fromParamArgs(array $args, $class=null)
    {
        if (!$args) {
            throw new \InvalidArgumentException();
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

    public function buildSet($meta)
    {
        $params = array();
        $clause = null;
        
        $fields = $meta ? $meta->getFields() : null;
        $named = $this->paramsAreNamed();
        
        if (is_string($this->set)) {
            $clause = $this->set;
        }
        else {
            $clause = [];
            foreach ($this->set as $name=>$value) {
                if (!($name == 0 && $name !== 0)) { // is_numeric($name)
                    // this allows arrays of manual "set"s, i.e. array('foo=foo+10', 'bar=baz')
                    $clause[] = $value;
                }
                else {
                    $field = (isset($fields[$name]) ? $fields[$name]['name'] : $name);

                    if ($named) {
                        $param = ':set_'.$name;
                        $clause[] = '`'.$field.'`='.$param;
                        $params[$param] = $value;
                    }
                    else {
                        $clause[] = '`'.$field.'`=?';
                        $params[] = $value;
                    }
                }
            }
            $clause = implode(', ', $clause);
        }
        return array($clause, $params);
    }
    
    public function buildQuery($meta)
    {
        $table = $this->table ?: $meta->table;
        
        list ($setClause,   $setParams)   = $this->buildSet($meta);
        list ($whereClause, $whereParams) = $this->buildClause($meta);
        
        $params = array_merge($setParams, $whereParams);
        if (count($params) != count($setParams) + count($whereParams)) {
            $intersection = array_intersect(array_keys($setParams), array_keys($whereParams));
            throw new Exception("Param overlap between set and where clause. Duplicated keys: ".implode(', ', $intersection));
        }
        
        if (!$whereClause)
            throw new \InvalidArgumentException("No where clause specified for table update. Explicitly specify 1=1 as the clause if you meant to do this.");
        
        $sql = "UPDATE $table SET $setClause WHERE $whereClause";
        
        return array($sql, $params);
    }
}
