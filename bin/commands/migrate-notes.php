<?php
$usage = <<<'DOCOPT'
Migrate Amiss v4 notes to v5

Usage: amiss migrate-notes [options] [--note=<note>]... [--ns=<ns>]...
                           <input>

<input>:
  PHP file or folder containing classes to migrate

Options:
  --note <note>            Search for all classes that have this note set at 
                           class level. Can specify more than once.
  --ns <ns>                Search for all classes in this namespace. Can specify more 
                           than once.

Use all classes in the Foo\Model namespace
    amiss create-tables --ns Foo\\Model

Use all classes in the Foo\Model and Bar\Model namespaces with the 
annotation ":foo = {}":
    amiss create-tables --ns Foo\\Model --ns Bar\\Model --note foo

DOCOPT;

$options = (new \Docopt\Handler)->handle($usage);
$optMapper = new \Amiss\Mapper\Arrays;
$meta = new \Amiss\Meta('stdClass', '', [
    'fields'=>[
        'input'=>'<input>',
        'notes'=>'--note',
        'namespaces'=>'--ns',
    ]
]);
$options = $optMapper->toObject($options, null, $meta);

goto class_defs; script:

$matchedNotes = [
    'has', 'field', 'primary', 'index', 'constructor', 'relation', 
    'canUpdate', 'canDelete', 'canInsert', 'readOnly', 'table', 'type',
    'getter', 'setter', 'key',
];
$parser = new LegacyParser();

$file = $options->input;

$code = file_get_contents($file);
$tokens = token_get_all($code);

$docComments = [];
$lastWsp = null;
foreach ($tokens as $token) {
    $token = (array)$token;
    if (!isset($token[1])) {
        $token = [null, $token[0], null];
    }
    if ($token[0] == T_WHITESPACE) {
        $lastWsp = $token[1];
    }
    if ($token[0] == T_DOC_COMMENT) {
        $docComments[] = [$token[1], $lastWsp];
    }
    if ($token[0] != T_WHITESPACE) {
        $lastWsp = null;
    }
}

foreach ($docComments as list($lastWsp, $doc)) {
    list ($data, $delete) = $parser->parse($parser->stripDocComment($doc));

    $newDoc = $doc;
    foreach ($delete as $del) {
        $qdel = preg_quote($del, '~');
        $newDoc = preg_replace("~(/\*\*\h*){$qdel}\h*\n?~", '$1', $newDoc, 1, $aCount);
        $newDoc = preg_replace("~^\h*\*\h*{$qdel}\h*\n?~m", '', $newDoc, 1, $bCount);
        if ($del && !$aCount && !$bCount) {
            throw new \UnexpectedValueException($del);
        }
    }

    if (preg_match("~^ /\*\* [\s\*]* \*/ $~x", trim($newDoc))) {
        $newDoc = '';
    }

    $newData = data_rebuild($data);
    $newNote = ":amiss = ".(json_encode($newData, JSON_PRETTY_PRINT)).";";

    $newNoteLined = " * ".implode("\n * ", explode("\n", $newNote))."\n";
    $indent = indent_detect($doc, $lastWsp);
    $newNoteLined = indent($newNoteLined, $indent);

    if (!$newDoc) {
        $newDoc = "/**\n$newNoteLined */";
    } else {
        $newDoc = preg_replace("~(\*/\s*)$~", "*\n$newNoteLined */", $newDoc);
    }
    
    $code = preg_replace('~'.preg_quote($doc, '~').'~', $newDoc, $code, 1, $count);
    if (!$count) {
        throw new \UnexpectedValueException();
    }
}

echo $code;

function indent_detect($doc, $lastWsp)
{
    if ($lastWsp == null) {
        return '';
    }
    if (preg_match('~\h+$~', $lastWsp, $match)) {
        var_dump($match[0]);
    }
}

function indent($doc, $indent)
{
    return $doc;
}

function data_rebuild($data)
{
    $isDefinitelyClass = 
           isset($data['table'])
        || isset($data['relation'])
        || isset($data['canUpdate'])
        || isset($data['canInsert'])
        || isset($data['canDelete'])
    ;
    $isDefinitelyProp =
           isset($data['has'])
        || isset($data['field'])
        || isset($data['getter'])
        || isset($data['setter'])
        || isset($data['primary'])
        || isset($data['key'])
        || (isset($data['index']) && $data['index'] === true)
    ;
    if ($isDefinitelyProp && $isDefinitelyClass) {
        throw new \LogicException();
    }

    $rebuilt = [];
    if ($isDefinitelyClass) {
        if (isset($data['canUpdate'])) {
            $rebuilt['canUpdate'] = $data['canUpdate'] == true;
        }
        if (isset($data['canInsert'])) {
            $rebuilt['canInsert'] = $data['canInsert'] == true;
        }
        if (isset($data['canDelete'])) {
            $rebuilt['canDelete'] = $data['canDelete'] == true;
        }
        if (isset($data['table'])) {
            $rebuilt['table'] = $data['table'];
        }
        if (isset($data['readOnly'])) {
            $rebuilt['readOnly'] = $data['readOnly'] == true;
        }
        if (isset($data['index'])) {
            $rebuilt['indexes'] = $data['index'];
        }
        if (isset($data['relation'])) {
            $rebuilt['relations'] = [];
            foreach ($data['relation'] as $k=>$rel) {
                $type = key($rel);
                $newRel = array_merge(['type'=>$type], $rel[$type]);
                $rebuilt['relations'][$k] = $newRel;
            }
        }
    }
    elseif ($isDefinitelyProp && isset($data['has'])) {
        $rebuilt = ['has'=>[]];
        if (isset($data['getter'])) {
            $rebuilt['field']['getter'] = $data['getter'];
        }
        if (isset($data['setter'])) {
            $rebuilt['field']['setter'] = $data['setter'];
        }
        $type = key($data['has']);
        $rebuilt['has']['type'] = $type;
        $rebuilt['has'] = array_merge($rebuilt['has'], $data['has']); 
    }
    elseif ($isDefinitelyProp) {
        $rebuilt = ['field'=>[]];
        if (isset($data['getter'])) {
            $rebuilt['field']['getter'] = $data['getter'];
        }
        if (isset($data['setter'])) {
            $rebuilt['field']['setter'] = $data['setter'];
        }
        if (isset($data['primary'])) {
            $rebuilt['field']['primary'] = true;
        }
        if (isset($data['field'])) {
            if ($data['field'] === true) {
                $rebuilt['field'] = true;
            } else {
                $rebuilt['field']['name'] = $data['field'];
            }
        }
        foreach (['index', 'key'] as $ntype) {
            if (isset($data[$ntype])) {
                if ($rebuilt['field'] === true) {
                    $rebuilt['field'] = [];
                }
                if ($data[$ntype] === true) {
                    $rebuilt['field'][$ntype] = true;
                } elseif (is_string($data[$ntype])) {
                    throw new \Exception("Not supported in new model");
                } elseif (!is_array($data[$ntype])) {
                    throw new \Exception("Not supported in new model");
                } else {
                    unset($data[$ntype]['fields']);
                    $rebuilt['field'][$ntype] = $data[$ntype];
                }
            }
        }
    }

    return $rebuilt;
}

return;
class_defs:
class LegacyParser
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

    public function stripDocComment($docComment)
    {
        // docblock start
        $docComment = preg_replace('@\s*/\*+\s*@', '', $docComment);
        
        // docblock end
        $docComment = preg_replace('@\s*\*+/\s*$@', '', $docComment);
        
        // docblock margin
        $docComment = preg_replace('@^\h*\*\h*@mx', '', $docComment);

        return ($docComment);
    }
    
    // TODO: this should use a proper parser for the keys. it should be simple 
    // to do, performance is not an issue here because the result of this should
    // ALWAYS be cached somehow, and it allows better error handling. this 
    // will do for now, but it's definitely one to revisit.
    public function parse($string)
    {
        global $matchedNotes;

        $keyPrefixLen = $this->keyPrefix ? strlen($this->keyPrefix) : 0;
        
        $data = array();
        $toDelete = [];
        $lines = preg_split('@\n@', $string, null, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_OFFSET_CAPTURE);

        foreach ($lines as list($l, $offset)) {
            $len = strlen($l);
            $l = trim($l);
            $offset += $len - strlen($l);

            if ($l && $l[0] == '@') {
                $curDelete = trim($l);
                $l = substr($l, 1);
                $d = preg_split('/\s+/', $l, 2, PREG_SPLIT_OFFSET_CAPTURE);
                
                $key = $d[0][0];
                if ($this->keyPrefix) {
                    if (strpos($key, $this->keyPrefix)!==0)
                        continue;
                    $key = substr($key, $keyPrefixLen);
                }
                
                $key = preg_split(
                    "/(
                           \]\[
                        |  \[
                        |  \]\.   # when php style abuts dot notation (foo[a].b)
                        |  \]
                        |  \.)
                    /x", 
                    rtrim($key, ']')
                );
                
                if (isset($d[1])) {
                    $value = $d[1][0];
                }
                else {
                    $value = $this->defaultValue;
                }
                
                $current = &$data;
                $found = array();
                foreach ($key as $part) {
                    if ($current && !is_array($current))
                        throw new \UnexpectedValueException("Key at path ".implode('.', $found)." already had non-array value, tried to set key $part");

                    if (!$found) {
                        if (in_array($part, $matchedNotes)) {
                            $toDelete[] = $curDelete;
                        }
                    }
                    
                    $found[] = $part;
                    
                    // if the last segment is empty, it means "@key[] value" or "@key. value" was used,
                    //  so we should just assign the next key
                    if ($part !== "")
                        $current = &$current[$part];
                    else
                        $current = &$current[];
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
        return [$data, $toDelete];
    }
}

goto script;

