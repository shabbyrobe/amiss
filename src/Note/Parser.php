<?php
namespace Amiss\Note;

class Parser
{
    const S_NONE = 0;
    const S_NAME = 1;
    const S_JSON_START = 2;
    const S_JSON = 3;

    public function parseClass($class)
    {
        if (!$class instanceof \ReflectionClass) {
            $class = new \ReflectionClass($class);
        }

        $info = new \stdClass;
        $info->notes = null;
        
        $doc = $class->getDocComment();
        if ($doc) {
            $info->notes = $this->parse($this->stripDocComment($doc));
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
                try {
                    $notes[$name] = $this->parse($this->stripDocComment($comment));
                }
                catch (\Exception $ex) {
                    throw new \RuntimeException("Failed parsing reflector {$r->name}: ".$ex->getMessage(), null, $ex);
                }
            }
        }
        return $notes;
    }
    
    public function parse($string)
    {
        $tokens = preg_split(
            '~ ( ^\h*: | = | \{ | \} ) ~xm', 
            $string, null, 
            PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE
        );

        $state = self::S_NONE;
        $curName = null;
        $jsonBuf = null;
        $jsonDepth = 0;

        $parsed = [];

        foreach ($tokens as $tok) {
            if ($state == self::S_NONE) {
                if (ltrim($tok) == ':') {
                    $state = self::S_NAME;
                    $curName = null;
                }
            }
            elseif ($state == self::S_NAME) {
                if ($tok == '=') {
                    $state = self::S_JSON_START;
                }
                elseif ($curName) {
                    throw new \Exception("Already got a name");
                }
                else {
                    $curName = trim($tok);
                    if (!$curName) {
                        throw new \Exception("Empty name");
                    }
                }
            }
            elseif ($state == self::S_JSON_START) {
                if (!trim($tok)) {
                    continue;
                }
                elseif ($tok != '{') {
                    throw new \Exception();
                }
                $jsonBuf = '{';
                $jsonDepth = 1;
                $state = self::S_JSON;
            }
            elseif ($state == self::S_JSON) {
                if ($tok == '{') {
                    $jsonBuf .= $tok;
                    ++$jsonDepth;
                }
                elseif ($tok == '}') {
                    --$jsonDepth;
                    if ($jsonDepth == 0) {
                        $jsonBuf .= $tok;
                        $parsed[$curName] = $jsonBuf;
                        $curName = null;
                        $jsonBuf = null;
                        $state = self::S_NONE;
                    }
                    elseif ($jsonDepth > 0) {
                        $jsonBuf .= $tok;
                    }
                    elseif ($jsonDepth < 0) {
                        throw new \LogicException();    
                    }
                }
                else {
                    $jsonBuf .= $tok;
                }
            }
        }

        if ($curName || $jsonBuf) {
            throw new \Exception("Unexpected end of JSON for key $curName");
        }

        $out = [];
        foreach ($parsed as $key=>$json) {
            $cur = json_decode($json, !!'assoc');
            if ($cur === null && ($err = json_last_error())) {
                throw new \Exception("JSON parsing failed for $key: ".json_last_error_msg());
            }
            $out[$key] = $cur;
        }
        return $out;
    }

    public function stripDocComment($docComment)
    {
        // docblock start
        $docComment = preg_replace('~ \s* / \*+ \s* ~x', '', $docComment);
        
        // docblock end
        $docComment = preg_replace('~ \s* \*+ / \s* $~x', '', $docComment);
        
        // docblock margin
        $docComment = preg_replace('~^ \h* \* \h? ~mx', '', $docComment);

        return $docComment;
    }
}
