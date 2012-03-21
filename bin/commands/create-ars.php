<?php 

throw new Exception("Not updated for 2.0!");

require_once(__DIR__.'/../../src/Loader.php');
require_once(__DIR__.'/../lib/functions.php');
spl_autoload_register(array(new Amiss\Loader, 'load'));

$usage = "amiss create-tables [OPTIONS] OUTDIR

Creates tables in the specified database

INPUT:
  PHP file or folder containing active records to create tables for

Options:
  --namespace     Place all records in this namespace (make sure you quote!)
  --dsn           Database DSN to use.
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

if (is_string($words))
	$words = explode(',', $words);

if (!$words)
	$words = array();

if ($wfile) {
	$words = array_merge($words, explode("\n", trim(file_get_contents($wfile))));
}

$sep = '-';

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
	
	$create = $connector->query("SHOW CREATE TABLE `".$table."`")->fetchColumn(1);
	$cols = substr($create, strpos($create, '(')+1);
	$cols = substr($cols, 0, strrpos($cols, ')'));
	
	$fields = array();
	
	if (preg_match_all("/^\s*`(?P<name>[^`]+)`(\s+(?P<type>.*?),?)?$/m", $cols, $matches, PREG_SET_ORDER)) {
		foreach ($matches as $match) {
			$field = lcfirst(preg_replace_callback('/_(.)/', function($match) { return strtoupper($match[1]); }, $match['name']));
			$fields[$field] = $match['type'];
		}
	}
	
	$result = $connector->query(sprintf("SHOW INDEX FROM `%s`", $table));
	
	$pricols = array();
	while ($row = $result->fetch( \PDO::FETCH_ASSOC)) {
		if ($row['Key_name'] == "PRIMARY") {
			$pricols[] = $row['Column_name'];
		}
	}
	
	if (count($pricols) > 1) {
		file_put_contents('php://stderr', 'Skipping multi-column primary on table '.$table.'. You should add an autoinc primary.'.PHP_EOL, FILE_APPEND);
		$pricols = array();
	}
	
	/*
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
			//	$relation->columns = array($columns[0], $relatedColumns[0]);
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
	
	$output .= "class ".$oname." extends \Amiss\Active\Record\n{\n";
	
	$output .= "    public static \$table = '".addslashes($table)."';\n";
	
	if ($pricols) {
		$output .= "    public static \$primary = '{$pricols[0]}';\n";
	}
	
	
	$output .= "\n    public static \$fields = array(\n";
	foreach ($fields as $f=>$type) {
		$output .= "        '$f'=>'".addslashes($type)."',\n";
	}
	$output .= "    );\n\n";
	
	$output .= "    public static \$relations = array();\n";
	
	$output .= "}\n";
	
	file_put_contents($outdir.'/'.$oname.'.php', $output);
}
