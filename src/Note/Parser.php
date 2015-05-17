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
            try {
                $info->notes = $this->parse($this->stripDocComment($doc));
            }
            catch (ParseException $ex) {
                throw new ParseException("Failed parsing class docblock {$class->name}: ".$ex->getMessage(), null, $ex);
            }
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
                    $curNotes = $this->parse($this->stripDocComment($comment));
                    if ($curNotes) {
                        $notes[$name] = $curNotes;
                    }
                }
                catch (ParseException $ex) {
                    throw new ParseException("Failed parsing reflector {$r->name}: ".$ex->getMessage(), null, $ex);
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
                    if (!$curName) {
                        throw new ParseException("Unexpected token '=', expected annotation name");
                    }
                    $state = self::S_JSON_START;
                }
                elseif ($curName) {
                    throw new ParseException("Annotation already has a name: '$curName'");
                }
                else {
                    $curName = trim($tok);
                }
            }
            elseif ($state == self::S_JSON_START) {
                if (!trim($tok)) {
                    continue;
                }
                elseif ($tok != '{') {
                    throw new ParseException("Unexpected token '".trim($tok)."', expected JSON start '{'");
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

        if ($state == self::S_JSON) {
            throw new ParseException("Unexpected end of JSON for key $curName");
        }
        elseif ($state != self::S_NONE) {
            throw new ParseException("Unexpected end of definition for key '$curName'");
        }

        $out = [];
        foreach ($parsed as $key=>$json) {
            $cur = json_decode($json, !!'assoc');
            if ($cur === null && ($err = json_last_error())) {
                throw new ParseException("JSON parsing failed for $key: ".json_last_error_msg());
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
