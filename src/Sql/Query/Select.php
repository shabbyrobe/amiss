<?php
namespace Amiss\Sql\Query;

class Select extends Criteria
{
    public $args=array();
    public $page;
    public $limit;
    public $offset = 0;
    public $fields;
    public $order = [];
    public $forUpdate = false;
    public $follow = true;
    public $with = [];

    public function getLimitOffset()
    {
        if ($this->limit) {
            return [$this->limit, $this->offset];
        } else if ($this->page) {
            return [$this->page[1], ($this->page[0] - 1) * $this->page[1]]; 
        }
    }

    public function buildQuery($meta)
    {
        $table = $this->table ?: $meta->table;
        
        list ($where, $params, $properties) = $this->buildClause($meta);
        $order = $this->buildOrder($meta);
        list ($limit, $offset) = $this->getLimitOffset();

        $t = ($meta->schema ? "`{$meta->schema}`." : null)."`{$table}`";
        $query = "SELECT ".$this->buildFields($meta)." FROM $t "
            .($where  ? "WHERE $where "            : '').' '
            .($order  ? "ORDER BY $order "         : '').' '
            .($limit  ? "LIMIT  ".(int)$limit." "  : '').' '
            .($offset ? "OFFSET ".(int)$offset." " : '').' '

            .($this->forUpdate ? 'FOR UPDATE' : '')
        ;
        
        return array($query, $params, $properties);
    }
    
    public function buildFields($meta, $tablePrefix=null)
    {
    // Careful! $meta might be null.
        $metaFields = $meta ? $meta->fields : null;
        
        $fields = $this->fields;
        if (!$fields) {
            $fields = $metaFields ? array_keys($metaFields) : '*';
        }
        
        if (is_array($fields)) {
            $fq = '';
            foreach ($fields as $f) {
                if ($fq) {
                    $fq .= ', ';
                }

                $table = null;
                if (isset($metaFields[$f])) {
                    $mf = $metaFields[$f];
                    if (!isset($mf['source'])) {
                        $name  = $mf['name'];
                        $alias = null;
                    }
                    else {
                        $name  = $mf['source'];
                        $alias = $mf['name'];
                    }
                    $table = isset($mf['table']) ? $mf['table'] : $tablePrefix;
                }
                else {
                    $name   = $f;
                    $alias  = null;
                    $table = $tablePrefix;
                }

                $fq .= ($table ?        ($table[0] == '`' ? $table : '`'.$table.'`.') : '') .
                       ($name  ?        ($name[0]  == '`' ? $name  : '`'.$name.'`')   : '') .
                       ($alias ? ' AS '.($alias[0] == '`' ? $alias : '`'.$alias.'`')  : '')
                ;
            }
            $fields = $fq;
        }
        
        return $fields;
    }

    // damn, this is pretty much identical to the above. FIXME, etc.
    public function buildOrder($meta, $tableAlias=null)
    {
        // Careful! $meta might be null.
        $metaFields = $meta ? $meta->fields : null;
        
        $order = $this->order;
        if ($meta && $meta->defaultOrder && $order !== null) {
            $order = $meta->defaultOrder;
        }

        if ($order) {
            if (is_array($order)) {
                $oClauses = '';
                foreach ($order as $field=>$dir) {
                    if ($oClauses) {
                        $oClauses .= ', ';
                    }
                    if (!is_string($field)) {
                        $field = $dir; $dir = '';
                    }
                    
                    $name = (isset($metaFields[$field]) ? $metaFields[$field]['name'] : $field);
                    $qname = $name[0] == '`' ? $name : '`'.$name.'`';
                    $qname = ($tableAlias ? $tableAlias.'.' : '').$qname;
                    $oClauses .= $qname;

                    if ($dir) {
                        if ($dir === true) {
                            $dir = 'asc';
                        }
                        // strpos(strtolower($dir), 'desc') === 0;
                        if (isset($dir[3]) && ($dir[0] == 'd' || $dir[0] == 'D') && ($dir[1] == 'e' || $dir[1] == 'E') && ($dir[2] == 's' || $dir[2] == 'S') && ($dir[3] == 'c' || $dir[3] == 'C')) {
                            $oClauses .= ' desc';
                        }
                        elseif (!isset($dir[2]) || !(($dir[0] == 'a' || $dir[0] == 'A') && ($dir[1] == 's' || $dir[1] == 'S') && ($dir[2] == 'c' || $dir[2] == 'C'))) {
                            throw new \UnexpectedValueException("Order direction must be 'asc' or 'desc', found ".$dir);
                        }
                    }
                }
                $order = $oClauses;
            }
            else {
                if ($metaFields && strpos($order, '{') !== false) {
                    $order = static::replaceFields($meta, $order, $tableAlias);
                }
            }
        }
        
        return $order;
    }
}
