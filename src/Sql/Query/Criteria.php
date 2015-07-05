<?php
namespace Amiss\Sql\Query;

use Amiss\Sql;

class Criteria extends Sql\Query
{
    public $where;
    public $params=array();

    // this hack is for the auto relations circular ref hack
    public $stack = [];
    
    public static function fromParamArgs(array $args, $class=null)
    {
        $c = $class ?: get_called_class();
        if ($args && $args[0] instanceof Sql\Query) {
            if (!$args[0] instanceof $c) {
                throw new \InvalidArgumentException("Expected $c, found ".get_class($args[0]));
            }
            return $args[0];
        }

        $q = new $c;
        if ($args) {
            $q->setParams($args);
        }
        return $q;
    }

    /**
     * Allows functions to have different query syntaxes:
     * func(..., 'pants=? AND foo=?', ['pants', 'foo'])
     * func(..., 'pants=:pants AND foo=:foo', array('pants'=>'pants', 'foo'=>'foo'))
     * func(..., array('where'=>'pants=:pants AND foo=:foo', 'params'=>array('pants'=>'pants', 'foo'=>'foo')))
     */
    public function setParams(array $args)
    {
        // the class name / meta has already been eaten from the args by this point

        if (!isset($args[1]) && is_array($args[0])) {
        // Array criteria: func(..., ['where'=>'', 'params'=>'']);
            $this->populate($args[0]);
        }

        elseif (!is_array($args[0])) {
        // Args criteria: func(..., 'a=? AND b=?',   ['a', 'b']);
        // Args criteria: func(..., 'a=:a AND b=:a', ['a'=>'a', 'b'=>'b']);
            $this->where = $args[0];
            if (isset($args[1])) {
                if (!is_array($args[1])) {
                    throw new \InvalidArgumentException("This is no longer variadic");
                }
                $this->params = $args[1];
            }
        } 
        else {
            throw new \InvalidArgumentException("Couldn't parse arguments");
        }
    }
    
    public function buildClause($meta, &$fidx=0)
    {
        $where = $this->where;
        $params = array();
        $namedParams = $this->paramsAreNamed(); 
        $properties = [];
        
        $fields = null;
        if ($meta) {
            $fields = $meta->getFields();
        }
        
        if (is_array($where)) {
            // build a where clause from an array

            $wh = array();
            foreach ($where as $p=>$v) {
                $name = $p;
                $table = null;
                if (isset($fields[$name])) {
                    $mf = $fields[$name];
                    $name  = !isset($mf['source']) ? $mf['name']  : $mf['source'];
                    $table =  isset($mf['table'])  ? $mf['table'] : null;
                }
                $qk = ($table ? ($table[0] == '`' ? $table : '`'.$table.'`.') : '') .
                      ($name  ? ($name[0]  == '`' ? $name  : '`'.$name .'`' ) : '');

                if (is_array($v)) {
                    $inKeys = [];
                    foreach (array_unique($v, SORT_REGULAR) as $val) {
                        $inKey = ':zp_'.$fidx++;
                        $params[$inKey] = $val;
                        $properties[$p][] = $inKey;
                        $inKeys[] = $inKey;
                    }
                    $wh[] = "$qk IN(".implode(',', $inKeys).")";
                }
                else {
                    $pidx = ':zp_'.$fidx++;
                    $wh[] = "$qk=$pidx";
                    $properties[$p] = $pidx;
                    $params[$pidx] = $v;
                }
            }
            $where = implode(' AND ', $wh);
            $namedParams = true;
        }
        else {
            // the where clause is a string, so replace '{prop}' tokens
            // with field names
            if ($fields && strpos($where, '{') !== false) {
                $where = static::replaceFields($meta, $where);
            }
        }

        if ($namedParams) {
            // if the parameters are named parameters, normalise them and handle
            // dynamic IN clauses. there is some repeated logic with the array where
            // handler above, which could perhaps be reduced.

            foreach ($this->params as $p=>$v) {
                if (is_string($p) && $p[0] != ':') {
                    $k = ':'.$p;
                    $fields && isset($fields[$p]) && $properties[$p] = $k;
                }
                else {
                    $k = $p;
                }
                if (is_array($v)) {
                    $inKeys = array();
                    $v = array_unique($v, SORT_REGULAR);
                    foreach ($v as $val) {
                        $inKey = ':zp_'.$fidx++;
                        $params[$inKey] = $val;
                        $inKeys[] = $inKey;
                    }
                    $inClause = "IN(".implode(',', $inKeys).")";

                    $qk = preg_quote($k, '@');
                    $where = preg_replace(
                        " @ IN \s* \( \s* ($qk) \s* \) @ix", 
                        $inClause, 
                        $where
                    );
                }
                else {
                    $params[$k] = $v;
                }
            }
        }
        else {
            $params = $this->params;
        }

        return array($where, $params, $properties);
    }
    
    public function paramsAreNamed()
    {
        if (is_array($this->where)) {
            return true;
        }
        foreach ($this->params as $k=>$v) {
            if ($k==0 && $k!==0) {
                return true;
            }
        }
    }
    
    public static function replaceFields(\Amiss\Meta $meta, $clause, $tableAlias=null)
    {
        $tokens = array();
        foreach ($meta->getFields() as $k=>$v) {
            $rep = !isset($v['source']) ? $v['name'] : $v['source'];
            if ($rep[0] != '`') {
                $rep = '`'.$rep.'`';
            }
            if (isset($v['table'])) {
                $tableAlias = $v['table'];
            }
            if ($tableAlias) {
                $rep = ($tableAlias[0] != '`' ? '`'.$tableAlias.'`' : $tableAlias).'.'.$rep;
            }
            $tokens['{'.$k.'}'] = $rep;
        }
        $clause = strtr($clause, $tokens);
        
        // cheapie hacko way to make sure tokens are substituted - not ideal.
        // may use Tempe once the C version is done.
        if (preg_match_all('/\{[A-Za-z\d-\_]+\}/', $clause, $matches)) {
            throw new \UnexpectedValueException("Unsubstituted tokens left in clause: ".implode(", ", $matches[0]));
        }

        return $clause;
    }
}
