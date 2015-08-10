<?php
$usage = <<<'DOCOPT'
Migrate Amiss v4 notes to v5

Usage: amiss migrate-notes [options] <input>...
       amiss migrate-notes --help

Displays a diff of each file changed to STDOUT, or rewrites the files in place
if you pass the `--in-place` flag.

<input>:
  PHP file(s) or folder(s) containing classes to migrate

Options:
  --no-colour      No colours in diff output
  --in-place       DO IT!
  --warn-only      Errors are treated as warnings, keeps processing
  --ext <ext>      File extension to look for [default: php]

If you set `--ext` to anything other than php, a less reliable docblock
extraction method will be used. This can yield more errors, but it means
you can use this script for things like documentation as well (it was
used on the Amiss docs).

DOCOPT;

$options = (new \Docopt\Handler)->handle($usage);
$optMapper = new \Amiss\Mapper\Arrays;
$meta = new \Amiss\Meta('stdClass', [
    'fields'=>[
        'input'=>'<input>',
        'inPlace'=>'--in-place',
        'noColour'=>'--no-colour',
        'noDiff'=>'--no-diff',
        'warnOnly'=>'--warn-only',
        'ext'=>'--ext',
    ]
]);
$options = $optMapper->mapRowToObject($meta, $options);
$options->crapExtractor = $options->ext != 'php';

$hasColorDiff = shell_cmd('which colordiff', false);
$diffCmd = (!$options->noColour && $hasColorDiff[0] == 0) ? 'colordiff' : 'diff';

goto class_defs; script:

$matchedNotes = [
    'has', 'field', 'primary', 'index', 'constructor', 'relation', 
    'canUpdate', 'canDelete', 'canInsert', 'readOnly', 'table', 'type',
    'getter', 'setter', 'key', 'fieldType',
];
$parser = new LegacyParser();

$iter = function() use ($options) {
    $iters = [];
    foreach ($options->input as $input) {
        if (is_dir($input)) {
            $iters[] = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($input), \RecursiveIteratorIterator::LEAVES_ONLY);
        } elseif (is_file($input)) {
            $iters[] = [$input];
        } else {
            throw new \Exception();
        }
    }
    foreach ($iters as $iter) {
        foreach ($iter as $i) { 
            if (is_dir($i)) {
                continue;
            }
            if (!preg_match("~\.{$options->ext}$~", $i)) {
                continue;
            }
            if (preg_match('~(^|[/\\\\])\.~', $i)) {
                continue;
            }
            if ($i instanceof \SplFileInfo) {
                $i = $i->getPathname();
            }
            yield $i;
        }
    }
};

$allOut = [];
$errors = [];
$hr = "----------------------------------------------------------";

foreach ($iter() as $file) {
    $code = file_get_contents($file);
    try {
        $out = comment_rewrite($code);
        stderr("Preparing $file\n");
        $diff = diff($code, $out);

        if ($options->inPlace) {
            $allOut[$file] = $out;
        }
        elseif ($diff) {
            ob_start();
            if ($options->noColour) {
                echo $file."\n";
            } else {
                echo "\e[92;1m{$file}\e[0m\n";
            }

            if (!$options->noDiff) {
                echo $diff;
                if ($options->noColour) {
                    echo "$hr\n";
                } else {
                    echo "\e[90m$hr\e[0m\n";
                }
            }
            $allOut[$file] = ob_get_clean();
        }
    }
    catch (RewriteException $rex) {
        $errors[] = "Rewriting $file failed: ".$rex->getMessage();
    }
}

if ($options->warnOnly || !$errors) {
    foreach ($allOut as $file=>$out) {
        if ($options->inPlace) {
            file_put_contents($file, $out);
        } else {
            echo $out;
        }
    }
}
if ($errors) {
    if ($options->noColour) {
        echo "ERRORS:\n";
    } else {
        echo "\e[91;1mERRORS:\e[0m\n";        
    }
    foreach ($errors as $e) {
        echo " - $e\n";
    }
}
if (!$options->warnOnly && $errors) {
    exit(1);
}

function comment_rewrite($code)
{
    global $parser;
    global $options;

    $nl = nl_detect($code);

    if ($options->crapExtractor) {
        $tokens = token_get_docblocks_manual($code);
    } else {
        $tokens = token_get_all($code);
    }

    $docComments = [];
    $prev = null;
    foreach ($tokens as $token) {
        $token = (array)$token;
        if (!isset($token[1])) {
            $token = [null, $token[0], null];
        }
        if ($token[0] == T_DOC_COMMENT) {
            $lastWsp = null;
            if ($prev && $prev[0] == T_WHITESPACE) {
                $lastWsp = $prev[1];
            }
            $docComments[] = [$token[1], $lastWsp];
        }
        $prev = $token;
    }

    foreach ($docComments as list($doc, $lastWsp)) {
        list ($data, $delete) = $parser->parse($parser->stripDocComment($doc));

        $newDoc = $doc;
        foreach ($delete as $del) {
            $qdel = preg_quote($del, '~');
            $newDoc = preg_replace("~(/\*\*\h*){$qdel}\h*\n?~", '$1', $newDoc, 1, $aCount);
            $newDoc = preg_replace("~^\h*\*\h*{$qdel}\h*\n?~m", '', $newDoc, 1, $bCount);
            if ($del && !$aCount && !$bCount) {
                throw new RewriteException($del);
            }
        }

        if (preg_match("~^ /\*\* [\s\*]* \*/ $~x", trim($newDoc))) {
            $newDoc = '';
        }

        $newData = data_rebuild($data);
        if ($newData) {
            $pretty = true;

            if (isset($newData['field']) && count($newData['field']) == 1 && (is_string($newData['field']) || $newData['field'] === true)) {
                // {"field": true}, {"field": "name"}
                $pretty = false;
            }
            elseif (isset($newData['field']) && count($newData['field']) == 1 && (isset($newData['field']['index']) || isset($newData['field']['primary']))) {
                // {"field": {"index": true}}, {"field": {"primary": true}}, 
                $pretty = false;
            }
            $newNote = ":amiss = ".(json_encode($newData, ($pretty ? JSON_PRETTY_PRINT : null))).";";

            $newNoteLined = " * ".implode("\n * ", explode("\n", $newNote))."\n";
            $indent = indent_detect($doc, $lastWsp);
            $newNoteLined = indent($newNoteLined, $indent, $nl);

            if (!$newDoc) {
                $newDoc = "/**\n$newNoteLined */";
            }
            else {
                // preg_replace requires backslashes to be escaped in the replacement pattern
                $newNoteReplace = str_replace("\\", "\\\\", $newNoteLined);
                $newDoc = preg_replace("~(\*/\s*)$~", "*\n$newNoteReplace */", $newDoc);
            }

            // preg_replace requires backslashes to be escaped in the replacement pattern
            $newDocReplace = str_replace("\\", "\\\\", $newDoc);

            $code = preg_replace('~'.preg_quote($doc, '~').'~', $newDocReplace, $code, 1, $count);
            if (!$count) {
                throw new RewriteException();
            }
        }
    }

    return $code;
}

function token_get_docblocks_manual($code)
{
    $tokens = [];
    $split = preg_split("~ ( /\*\*+ .*? \*/ | \s+ ) ~sx", $code, null, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
    foreach ($split as $tok) {
        if (strpos($tok, "/**") === 0) {
            $tokens[] = [T_DOC_COMMENT, $tok, null];
        }
        elseif (preg_match("/\s/", $tok[0])) {
            $tokens[] = [T_WHITESPACE, $tok, null];
        }
        else {
            $tokens[] = [null, $tok, null];
        }
    }
    return $tokens;
}

function nl_detect($code)
{
    $nl = "\n";
    $counts = [];
    if (preg_match_all('/\r?\n/', $code, $matches)) {
        foreach ($matches[0] as $match) {
            if (!isset($counts[$match])) {
                $counts[$match] = 0;
            }
            ++$counts[$match];
        }
    }
    if ($counts) {
        arsort($counts, SORT_NUMERIC);
        $nl = key($counts);
    }
    return $nl;
}

function diff($a, $b)
{
    global $diffCmd;

    $aFile = tempnam(sys_get_temp_dir(), 'amiss-');
    $bFile = tempnam(sys_get_temp_dir(), 'amiss-');
    file_put_contents($aFile, $a);
    file_put_contents($bFile, $b);
    
    list ($code, $stdout, $stderr) = shell_cmd("$diffCmd -u $aFile $bFile | tail -n +3", false);
    @unlink($aFile);
    @unlink($bFile);

    if ($code !== 0 && $code !== 1) {
        throw new \UnexpectedValueException("Diff failed: $stderr");
    }
    return $stdout;
}

function shell_cmd($cmd, $failOnNonZero=true)
{
    $proc = proc_open($cmd, [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes);
    fclose($pipes[0]);
    $result = stream_get_contents($pipes[1]);
    $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    
    $return = proc_close($proc);
    if ($failOnNonZero && $return != 0) {
        throw new \RuntimeException("Command $cmd failed with code $return, message $error\n");
    }
    
    return [$return, $result, $error];
}

function indent_detect($doc, $lastWsp)
{
    if ($lastWsp == null) {
        return '';
    }
    if (preg_match('~\h+$~', $lastWsp, $match)) {
        return $match[0];
    }
}

function indent($doc, $indent, $nl)
{
    $doc = preg_split("/\r?\n/", $doc);
    return $indent.implode("$nl$indent", $doc);
}

function data_rebuild($data)
{
    $isDefinitelyClass = 
           isset($data['table'])
        || isset($data['relation'])
        || isset($data['canUpdate'])
        || isset($data['canInsert'])
        || isset($data['canDelete'])
        || isset($data['fieldType'])
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
        throw new RewriteException();
    }

    $rebuilt = [];
    if ($isDefinitelyClass) {
        // it's a class!

        if (isset($data['table'])) {
            $exp = preg_split('/\s*\.\s*/', $data['table'], 2, PREG_SPLIT_NO_EMPTY);
            if (isset($exp[1])) {
                list ($rebuilt['schema'], $rebuilt['table']) = $exp;
            } else {
                $rebuilt['table'] = $data['table'];
            }
        }
        if (isset($data['canUpdate'])) {
            $rebuilt['canUpdate'] = $data['canUpdate'] == true;
        }
        if (isset($data['canInsert'])) {
            $rebuilt['canInsert'] = $data['canInsert'] == true;
        }
        if (isset($data['canDelete'])) {
            $rebuilt['canDelete'] = $data['canDelete'] == true;
        }
        if (isset($data['fieldType'])) {
            $rebuilt['fieldType'] = $data['fieldType'];
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
        // it's a relation!

        $rebuilt = ['has'=>[]];
        if (isset($data['getter'])) {
            $rebuilt['field']['getter'] = $data['getter'];
        }
        if (isset($data['setter'])) {
            $rebuilt['field']['setter'] = $data['setter'];
        }
        if (is_string($data['has'])) {
            $data['has'] = [$data['has']=>[]];
        }
        $type = key($data['has']);
        $rebuilt['has']['type'] = $type;
        $rebuilt['has'] = array_merge($rebuilt['has'], $data['has'][$type]); 
    }

    elseif ($isDefinitelyProp) {
        // it's a field!

        $rebuilt = ['field'=>[]];
        if (isset($data['name'])) {
            $rebuilt['field']['id'] = $data['name'];
        }
        if (isset($data['getter'])) {
            $rebuilt['field']['getter'] = $data['getter'];
        }
        if (isset($data['type'])) {
            $rebuilt['field']['type'] = $data['type'];
        }
        if (isset($data['setter'])) {
            $rebuilt['field']['setter'] = $data['setter'];
        }
        if (isset($data['primary'])) {
            $rebuilt['field']['primary'] = true;
        }
        if (isset($data['field'])) {
            if ($data['field'] !== true) {
                $rebuilt['field']['name'] = $data['field'];
            }
        }
        if (isset($data['readOnly'])) {
            $rebuilt['field']['readOnly'] = $data['readOnly'] == true;
        }
        elseif (isset($data['readonly'])) {
            $rebuilt['field']['readOnly'] = $data['readonly'] == true;
        }
        foreach (['index', 'key'] as $ntype) {
            if (isset($data[$ntype])) {
                if ($rebuilt['field'] === true) {
                    $rebuilt['field'] = [];
                }
                if ($data[$ntype] === true) {
                    $rebuilt['field'][$ntype] = true;
                } elseif (is_string($data[$ntype])) {
                    throw new RewriteException("String named indexes not supported in new model");
                } elseif (!is_array($data[$ntype])) {
                    throw new RewriteException("Index must be true or an array definition to be supported in new model");
                } else {
                    unset($data[$ntype]['fields']);
                    $rebuilt['field'][$ntype] = $data[$ntype];
                }
            }
        }
        if ($rebuilt['field'] == []) {
            $rebuilt['field'] = true;
        }
        elseif (count($rebuilt['field']) == 1 && isset($rebuilt['field']['name'])) {
            $rebuilt['field'] = $rebuilt['field']['name'];
        }
    }

    return $rebuilt;
}

return;
class_defs:

class RewriteException extends \RuntimeException {}

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
                    if ($current && !is_array($current)) {
                        throw new RewriteException("Key at path ".implode('.', $found)." already had non-array value, tried to set key $part");
                    }

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

function stderr($msg)
{
    file_put_contents('php://stderr', $msg, FILE_APPEND);
}

goto script;

