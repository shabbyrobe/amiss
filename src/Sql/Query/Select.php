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
        } else {
            return [$this->page[1], ($this->page[0] - 1) * $this->page[1]]; 
        }
    }

    public function buildQuery($meta)
    {
        $table = $this->table ?: $meta->table;
        
        list ($where, $params, $properties) = $this->buildClause($meta);
        $order = $this->buildOrder($meta);
        list ($limit, $offset) = $this->getLimitOffset();
        
        $query = "SELECT ".$this->buildFields($meta)." FROM $table "
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
        $metaFields = $meta ? $meta->getFields() : null;
        
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
        $metaFields = $meta ? $meta->getFields() : null;
        
        $order = $this->order;
        if ($meta && $meta->defaultOrder && $order !== null) {
            $order = $meta->defaultOrder;
        }

        if ($order) {
            if (is_array($order)) {
                $oClauses = array();
                foreach ($order as $field=>$dir) {
                    if (!($field == 0 && $field !== 0)) { // is_numeric($field)
                        $field = $dir; $dir = 'asc';
                    }
                    
                    $name = (isset($metaFields[$field]) ? $metaFields[$field]['name'] : $field);
                    $qname = $name[0] == '`' ? $name : '`'.$name.'`';
                    $qname = ($tableAlias ? $tableAlias.'.' : '').$qname;
                    $oClauses[] = $qname.($dir == 'asc' ? '' : ' desc');
                }
                $order = implode(', ', $oClauses);
            }
            else {
                if ($metaFields && strpos($order, '{')!==false) {
                    $order = static::replaceFields($meta, $order, $tableAlias);
                }
            }
        }
        
        return $order;
    }
}
