<?php
$usage = <<<'DOCOPT'
Creates classes from an existing database

Usage: amiss create-classes [options] <outdir>

Options:
  --ns <ns>          Place all records in this namespace (make sure you quote!)
  --dsn <dsn>        Database DSN to use.
  --base <base>      Base class to use for created classes
  --ars              Equivalent to --base \Amiss\Sql\ActiveRecord
  -u, --user <user>  Database user
  -p                 Prompt for database password 
  --getset           Use getters/setters instead of fields
  --password <pass>  Database password (don't use this, use -p instead)
  --words <words>    Comma separated word list file for table name translation
  --wfile <file>     Word list file for table name translation

Word list:
  Some databases don't have a table name separator. Pass a list or path to a
  list of words (all lower case) separated by newlines and these will be
  used to determine PascalCasing
DOCOPT;

$options = (new \Docopt\Handler)->handle($usage);
$mapper = new \Amiss\Mapper\Arrays;
$meta = new \Amiss\Meta('stdClass', [
    'fields'=>[
        'outdir'=>'<outdir>',
        'namespace'=>'--ns',
        'dsn'=>'--dsn',
        'prompt'=>'-p',
        'user'=>'--user',
        'password'=>'--password',
        'words'=>'--words',
        'wfile'=>'--wfile',
        'base'=>'--base',
        'getset'=>'--getset',
    ]
]);
$options = $mapper->mapRowToObject($options, null, $meta);

if ($options->base && $options->ars) {
    die("Cannot pass --base and --ars\n");
}

$options->outdir = $options->outdir ? realpath($options->outdir) : null;

if (!$options->outdir || !is_writable($options->outdir)) {
    echo "Outdir not passed, missing or not writable\n\n";
    echo $usage;
    exit(1);
}

if (!$options->dsn) {
    echo "DSN not specified\n\n";
    echo $usage;
    exit(1);
}

if ($options->prompt) {
    $options->password = prompt_silent("Password: ");
}
if (is_string($options->words)) {
    $options->words = explode(',', $options->words);
}
if (!$options->words) {
    $options->words = array();
}
if ($options->wfile) {
    $options->words = array_merge(
        $options->words,
        explode("\n", trim(file_get_contents($options->wfile)))
    );
}

$sep = '_';

$wtmp = $options->words;
$options->words = array();
foreach ($wtmp as $w) {
    $w = trim($w);
    if ($w) {
        $w = strtolower($w);
        $v = $w.$sep;
        $options->words[$w] = $v;
    }
}

$options->words = array_unique($options->words);

$connector = new \PDOK\Connector($options->dsn, $options->user, $options->password);
$stmt = $connector->query("SHOW TABLES");
while ($table = $stmt->fetchColumn()) {
    $oname = strtr($table, $options->words);
    $oname = preg_replace('/\d+/', '$0'.$sep, $oname);
    $oname = ucfirst(preg_replace_callback('/'.preg_quote($sep, '/').'(.)/', function($match) { return strtoupper($match[1]); }, rtrim($oname, $sep)));
    
    $tableFields = $connector->query("SHOW FULL FIELDS FROM $table")->fetchAll(\PDO::FETCH_ASSOC);
    
    $fields = array();
    $primary = array();
    foreach ($tableFields as $field) {
        $prop = lcfirst(preg_replace_callback('/_(.)/', function($match) { return strtoupper($match[1]); }, $field['Field']));
        
        $fields[$prop] = array('name'=>$field['Field'], 'type'=>$field['Type'], 'default'=>$field['Default']);
        if ($field['Null'] == 'YES')
            $fields[$prop]['type'] .= ' NULL';
        
        if (strpos($field['Key'], 'PRI')!==false) {
            $primary[] = $field['Field'];
            if (strpos($field['Extra'], 'auto_increment')!==false) {
                $fields[$prop]['type'] = 'autoinc';
            }
        }
    }
    
    /*
    $create = $connector->query("SHOW CREATE TABLE `".$table."`")->fetchColumn(1);
    $cols = substr($create, strpos($create, '(')+1);
    $cols = substr($cols, 0, strrpos($cols, ')'));
    
    $relations = array();
    if (\preg_match_all("/CONSTRAINT\s+\`(?P<name>[^\`]+)\`\s+FOREIGN KEY\s+\((?P<fields>[^\)]+)\)\s+REFERENCES\s+\`(?P<reftable>[^\`]+)\`\s+\((?P<reffields>[^\)]+)\)/", $cols, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $relation = array();
            
            $columns = array();
            $relatedColumns = array();
            
            $relation['name'] = $match['name'];
            foreach (explode(",", $match['fields']) as $f) {
                $columns[] = trim($f, '\` ');
            }
            foreach (explode(",", $match['reffields']) as $f) {
                $relatedColumns[] = trim($f, '\` ');
            }
            //if (count($columns) == 1) {
            //    $relation->columns = array($columns[0], $relatedColumns[0]);
            //}
            //else {
                $relation['columns'] = array($columns, $relatedColumns);
            //}
            $relation['relatedTableName'] = $match['reftable'];
            $relations[$relation['name']] = $relation;
        }
    }
    */
    
    $output = "<?php\n\n";
    if ($options->namespace) {
        $output .= "namespace {$options->namespace};\n\n";
    }
    
    $classNotes = [
        'table'=>$table,
    ];
    $output .= "/**\n * :amiss = ".json_encode($classNotes).";\n */\n";
    $output .= "class ".$oname.($options->base ? " extends ".$options->base : '')."\n{\n";
    
    foreach ($fields as $f=>&$details) {
        $fieldNotes = [
            'type'=>$details['type'],
        ];
        $isPrimary = in_array($details['name'], $primary);
        if ($isPrimary) {
            $fieldNotes['primary'] = true;
        }

        $details['notes'] = "    /**\n     * :amiss = ".json_encode(["field"=>$fieldNotes]).";\n     */\n";
        if ($options->getset) {
            $output .= "    private \$$f;\n";
        }
        else {
            $output .= $details['notes'];
            $output .= "    public \$$f;\n\n";
        }
    }
    unset($details);

    if ($options->getset) {
        $output .= "\n";
        foreach ($fields as $f=>$details) {
            $n  = ucfirst($f);
            $output .= $details['notes'];
            $output .= "    public function get$n()   { return \$this->\$f; }\n";
            $output .= "    public function set$n(\$v) { \$this->\$f = \$v; return \$this; }\n";
            $output .= "\n";
        }
    }
    
    $output .= "}\n\n";
    
    file_put_contents($options->outdir.'/'.$oname.'.php', $output);
}
