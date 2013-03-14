<?php
namespace Amiss\Note;

class Parser
{
    public $defaultValue;
    public $keyPrefix;
    
    public function __construct(array $config=null)
    {
        $defaultConfig = array(
            'defaultValue'=>true,
            'keyPrefix'=>null,
        );
        $config = $config ? array_merge($defaultConfig, $config) : $defaultConfig;
        
        $this->defaultValue = $config['defaultValue'];
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
        // docblock start
        $docComment = preg_replace('@\s*/\*+@', '', $docComment);
        
        // docblock end
        $docComment = preg_replace('@\*+/\s*$@', '', $docComment);
        
        // docblock margin
        $docComment = preg_replace('@^\s*\*\s*@mx', '', $docComment);
        
        return $this->parse($docComment);
    }
    
    public function parse($string)
    {
        $keyPrefixLen = $this->keyPrefix ? strlen($this->keyPrefix) : 0;
        
        $data = array();
        $lines = preg_split('@\n@', $string, null, PREG_SPLIT_NO_EMPTY);
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
                
                $key = preg_split(
                    "/(
                           \]\[
                        |  \[
                        |  \]\.   # php style abuts dot notation (foo[a].b)
                        |  \]
                        |  \.)
                    /x", 
                    rtrim($key, ']')
                );
                end($key);
                if (current($key) === "")
                    $key[key($key)] = '0';
                
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
