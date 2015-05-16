<?php
namespace Amiss\Sql\Query;

use Amiss\Sql;

class Criteria extends Sql\Query
{
    public $where;
    public $params=array();
    public $aliases=array();

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
        if ($meta) $fields = $meta->getFields();
        
        if (is_array($where)) {
            $wh = array();
            foreach ($where as $p=>$v) {
                $k = $p;
                if (isset($fields[$k])) {
                    $k = $fields[$k]['name'];
                    if (isset($this->aliases[$k])) {
                        $k = $this->aliases[$k];
                    }
                }
                $qk = $k[0] != '`' ?  '`'.str_replace('`', '', $k).'`' : $k;
                $pidx = 'zp_'.$fidx++;
                $wh[] = "$qk=:$pidx";
                $properties[$p] = ":$pidx";
                $params[":$pidx"] = $v;
            }
            $where = implode(' AND ', $wh);
            $namedParams = true;
        }
        else {
            if ($fields && strpos($where, '{') !== false) {
                $where = $this->replaceFieldTokens($fields, $where);
            }
        }

        if ($namedParams) {
            foreach ($this->params as $p=>$v) {
                // ($k == 0 && $k !== 0) == !is_numeric($k) (mostly. (string)"0" and (string)"0.0" don't work)
                if (($p == 0 && $p !== 0) && $p[0] != ':') {
                    $k = ':'.$p;
                    $fields && isset($fields[$p]) && $properties[$p] = $k;
                }
                else {
                    $k = $p;
                }
                if (is_array($v)) {
                    $inparms = array();
                    $v = array_unique($v);
                    foreach ($v as $val) {
                        $inparms[':zp_'.$fidx++] = $val;
                    }
                    $params = array_merge($params, $inparms);
                    $qk = preg_quote($k, '@');
                    $where = preg_replace(
                        " @ IN \s* \( \s* ($qk) \s* \) @ix", 
                        "IN(".implode(',', array_keys($inparms)).")",
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
    
    protected function replaceFieldTokens($fields, $clause, $tableAlias=null)
    {
        $tokens = array();
        foreach ($fields as $k=>$v) {
            $repl = null;
            if (isset($this->aliases[$v['name']])) {
                $repl = $this->aliases[$v['name']];
            } else {
                $repl = '`'.$v['name'].'`';
                if ($tableAlias) {
                    $repl = "$tableAlias.$repl";
                }
            }
            $tokens['{'.$k.'}'] = $repl;
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
