<?php

require_once(__DIR__.'/../../src/Loader.php');
require_once(__DIR__.'/../lib/functions.php');
Amiss\Loader::register();

$usage = "amiss create-classes [OPTIONS] OUTDIR

Creates tables in the specified database

Options:
  --namespace     Place all records in this namespace (make sure you quote!)
  --dsn           Database DSN to use.
  --base          Base class to use for created classes
  --ars           Equivalent to --base \Amiss\Active\Record
  -u, --user      Database user
  -p              Prompt for database password 
  --password      Database password (don't use this - use -p instead)
  --words w1[,w2] Comma separated word list file for table name translation
  --wfile file    Word list file for table name translation

Word list:
  Some databases don't have a table name separator. Pass a list or path to a
  list of words (all lower case) separated by newlines and these will be
  used to determine PascalCasing
";

$outdir = null;
$namespace = null;
$dsn = null;
$prompt = false;
$user = null;
$password = null;
$words = null;
$wfile = null;
$base = null;

$iter = new ArrayIterator(array_slice($argv, 1));
foreach ($iter as $v) {
    if ($v == '--user' || $v == '-u') {
        $iter->next();
        $user = $iter->current();
    }
    elseif ($v == '--namespace') {
        $iter->next();
        $namespace = $iter->current();
    }
    elseif ($v == '--password') {
        $iter->next();
        $password = $iter->current();
    }
    elseif ($v == '--ars') {
        $base = '\Amiss\Active\Record';
    }
    elseif ($v == '--base') {
        $iter->next();
        $base = $iter->current();
    }
    elseif ($v == '--password') {
        $iter->next();
        $password = $iter->current();
    }
    elseif ($v == '-p') {
        $prompt = true;
    }
    elseif ($v == '--dsn') {
        $iter->next();
        $dsn = $iter->current();
    }
    elseif ($v == '--words') {
        $iter->next();
        $words = $iter->current();
    }
    elseif ($v == '--wfile') {
        $iter->next();
        $wfile = $iter->current();
    }
    elseif (strpos($v, '--')===0 || $outdir) {
        echo "Invalid arguments\n\n";
        echo $usage;
        exit(1);
    }
    else {
        $outdir = $v;
    }
}

$outdir = $outdir ? realpath($outdir) : null;

if (!$outdir || !is_writable($outdir)) {
    echo "Outdir not passed, missing or not writable\n\n";
    echo $usage;
    exit(1);
}

if (!$dsn) {
    echo "DSN not specified\n\n";
    echo $usage;
    exit(1);
}

if ($prompt) {
    $password = prompt_silent("Password: ");
}

if (is_string($words))
    $words = explode(',', $words);

if (!$words)
    $words = array();

if ($wfile) {
    $words = array_merge($words, explode("\n", trim(file_get_contents($wfile))));
}

$sep = '_';

$wtmp = $words;
$words = array();
foreach ($wtmp as $w) {
    $w = trim($w);
    if ($w) {
        $w = strtolower($w);
        $v = $w.$sep;
        $words[$w] = $v;
    }
}

$words = array_unique($words);

$connector = new Amiss\Connector($dsn, $user, $password);

$stmt = $connector->query("SHOW TABLES");
while ($table = $stmt->fetchColumn()) {
    $oname = strtr($table, $words);
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
    if ($namespace) {
        $output .= "namespace $namespace;\n\n";
    }
    
    $output .= "/**\n * @table ".addslashes($table)."\n */\n";
    $output .= "class ".$oname.($base ? " extends ".$base : '')."\n{\n";
    
    foreach ($fields as $f=>$details) {
        $output .= "    /**\n";
        $isPrimary = in_array($details['name'], $primary);
        
        $output .= "     * @".($isPrimary ? 'primary' : 'field')."\n";
        $output .= "     * @type {$details['type']}\n";
        
        $output .= "     */\n";
        $output .= "    public \$$f;\n\n";
    }
    
    $output .= "}\n\n";
    
    file_put_contents($outdir.'/'.$oname.'.php', $output);
}
