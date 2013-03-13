<?php
require '/Users/blakewilliams/Code/php/fulfil/src/fulfil.php';

create_classes();

$p = new Parser();
var_dump($p->parseClass(new \ReflectionClass('Foo')));

function parse_complex($noteValue)
{
    $qs = trim(preg_replace('/\s*([=&])\s*/', '$1', str_replace(';', '&', $noteValue)));
    parse_str($qs, $data);
    return $data;
}

function handle_relation($noteValue)
{
    if (is_string($value)) {
        $split = preg_split('/\s+/', ltrim($value), 2, PREG_SPLIT_NO_EMPTY);
        $value = array($split[0]=>parse_complex($split[1]));
    }
    $value = array_merge(array(key($value)), array_values($value));
}

function create_classes() {

/**
 * @table      your_table
 * @fieldType  VARCHAR(255)
 * @ext.nestedSet
 */
class Foo
{
    /** @primary */
    public $id;

    /** @field some_column */
    public $name;

    /** @field */
    public $barId;

    /** 
     * One-to-many relation:
     * @has.many.of  Bar
     * @has.many.on  barId
     */
    public $bars;

    /**
     * One-to-one relation:
     * @has.one.of  Baz
     * @has.one.on  bazId
     */
    public $baz;

    // field is defined below using getter/setter
    private $fooDate;

    /**
     * @field
     * @type.id date
     * @type.timeZone UTC
     */
    public function getFooDate()
    {
        return $this->fooDate;
    }

    public function setFooDate($value)
    {
        $this->fooDate = $value;
    }
}

/*
 * One-to-many relation:
 * 
 * @has.many.of  Bar
 * @has.many.on  barId
 * 
 * @has one of=Bar; on=barId
 *
 *
 * @has.many.of Bar
 * @has.many.on.barId id
 * 
 * @has one of=Bar; on[barId]=id
 * 
 *
 * @has.many.of Bar
 * @has.many.on.barId otherBarId
 * @has.many.on.bazId otherBazId
 * @has.many.on.quxId otherQuxId
 * 
 * @has one of=Bar; on[barId]=otherBarId; on[bazId]=otherBazId; on[quxId]=otherQuxId
 * 
 */

class Parser
{
    const SEP_ARRAY = '/(\]\[|\[|\])/';
    const SEP_DOT = '/\./';
    
    public $defaultValue;
    public $keySeparatorPattern;
    public $keyPrefix;
    
    public function __construct(array $config=null)
    {
        $defaultConfig = array(
            'keySeparatorPattern'=>static::SEP_DOT,
            'defaultValue'=>true,
            'keyPrefix'=>null,
        );
        $config = $config ? array_merge($defaultConfig, $config) : $defaultConfig;
        
        $this->defaultValue = $config['defaultValue'];
        $this->keySeparatorPattern = $config['keySeparatorPattern'];
        $this->keyPrefix = $config['keyPrefix'];
    }
    
    public function parseClass(\ReflectionClass $class)
    {
        $info = new \stdClass;
        $info->notes = null;
        
        $doc = $class->getDocComment();
        if ($doc) {
            $info->notes = $this->parseDocComment($doc);
        }
        
        $info->methods = $this->parseReflectors($class->getMethods());
        $info->properties = $this->parseReflectors($class->getProperties());
        
        return $info;
    }
    
    public function parseReflectors($reflectors)
    {
        $notes = array();
        foreach ($reflectors as $r) {
            $comment = $r->getDocComment();
            $name = $r->name;
            if ($comment) {
                $notes[$name] = $this->parseDocComment($comment);
            }
        }
        return $notes;
    }
    
    public function parseDocComment($docComment)
    {
        $keyPrefixLen = $this->keyPrefix ? strlen($this->keyPrefix) : 0;
        
        // docblock start
        $docComment = preg_replace('@\s*/\*+@', '', $docComment);
        
        // docblock end
        $docComment = preg_replace('@\*+/\s*$@', '', $docComment);
        
        // docblock margin
        $docComment = preg_replace('@^\s*\*\s*@mx', '', $docComment);
        
        $data = array();
        $lines = preg_split('@\n@', $docComment, null, PREG_SPLIT_NO_EMPTY);
        foreach ($lines as $l) {
            $l = trim($l);
            if ($l && $l[0] == '@') {
                $l = substr($l, 1);
                $d = preg_split('/\s+/', $l, 2);
                
                $key = $d[0];
                if ($this->keyPrefix) {
                    if (strpos($key, $this->keyPrefix)!==0)
                        continue;
                    $key = substr($key, $keyPrefixLen);
                }   
                
                $key = $this->keySeparatorPattern 
                    ? preg_split($this->keySeparatorPattern, $key, null, PREG_SPLIT_NO_EMPTY)
                    : array($key)
                ;
                
                $value = isset($d[1]) ? $d[1] : $this->defaultValue;
                
                $current = &$data;
                $found = array();
                foreach ($key as $part) {
                    if ($current && !is_array($current))
                        throw new \UnexpectedValueException("Key at path ".implode('.', $found)." already had non-array value, tried to set key $part");
                    
                    $found[] = $part;
                    $current = &$current[$part];
                }
                if ($current === null)
                    $current = $value;
                elseif (!is_array($current))
                    $current = array($current, $value);
                else
                    $current[] = $value;
                
                unset($current);
            }
        }
        return $data;
    }
}

class DocBlock
{
    const SEP_ARRAY = '/(\]\[|\[|\])/';
    const SEP_DOT = '/\./';
    
    public $docComment;
    public $data;
    
    public function __construct($docComment, $keySeparatorPattern=null)
    {
        $this->docComment = $docComment;
        $this->data = static::parseDocComment($docComment, $keySeparatorPattern);
    }
    
    public static function parseClass(\ReflectionClass $class, $keySeparatorPattern=null)
    {
        $info = new \stdClass;
        $info->notes = null;
        
        $doc = $class->getDocComment();
        if ($doc) {
            $info->notes = new static($doc, $keySeparatorPattern);
        }
        
        $info->methods = $this->parseReflectors($class->getMethods(), $keySeparatorPattern);
        $info->properties = $this->parseReflectors($class->getProperties(), $keySeparatorPattern);
        
        return $info;
    }
    
    public static function parseReflectors($reflectors, $keySeparatorPattern=null)
    {
        $notes = array();
        foreach ($reflectors as $r) {
            $comment = $r->getDocComment();
            $name = $r->name;
            if ($comment) {
                $notes[$name] = new static($doc, $keySeparatorPattern);
            }
        }
        return $notes;
    }
    
    public static function parseDocComment($docComment, $keySeparatorPattern=null)
    {
        $keySeparatorPattern = $keySeparatorPattern ?: static::SEP_ARRAY;
        
        // docblock start
        $docComment = preg_replace('@\s*/\*+@', '', $docComment);
        
        // docblock end
        $docComment = preg_replace('@\*+/\s*$@', '', $docComment);
        
        // docblock margin
        $docComment = preg_replace('@^\s*\*\s*@mx', '', $docComment);
        
        $data = array();
        
        $lines = preg_split('@\n@', $docComment, null, PREG_SPLIT_NO_EMPTY);
        foreach ($lines as $l) {
            $l = trim($l);
            if ($l && $l[0] == '@') {
                $l = substr($l, 1);
                $d = explode(' ', $l, 2);
                
                $value = isset($d[1]) ? $d[1] : true;
                $key = $keySeparatorPattern ? preg_split($keySeparatorPattern, $d[0], null, PREG_SPLIT_NO_EMPTY) : array($d[0]);
                
                $current = &$data;
                foreach ($key as $part) {
                    $current = &$current[$part];
                }
                if ($current === null)
                    $current = $value;
                elseif (!is_array($current))
                    $current = array($current, $value);
                else
                    $current[] = $value;
                
                unset($current);
            }
        }
        
        return $data;
    }
    
    public function getValue($key)
    {
        if (!$key)
            throw new \InvalidArgumentException();
        
        if (!is_array($key))
            $key = array($key);
        
        $value = $this->data;
        foreach ($key as $i) {
            if (!isset($value[$i])) 
                throw new \UnexpectedValueException(sprintf("Key %s not found", $this->keyString($key)));
            
            $value = $value[$i];
        }
        
        return $value;
    }
}

}
