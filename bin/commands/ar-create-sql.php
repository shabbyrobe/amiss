<?php

require_once(__DIR__.'/../../src/Loader.php');
require_once(__DIR__.'/../lib/functions.php');
spl_autoload_register(array(new Amiss\Loader, 'load'));

$usage = "amiss ar-create-sql [OPTIONS] INPUT

Emits SQL for creating active record tables

INPUT:
  PHP file or folder containing active records to create tables for

Options:
  --engine        Database engine (mysql, sqlite)
  --bootstrap     File to be run before creating tables
  -r, --recurse   Recurse all input directories looking for active records
";

$bootstrap = null;
$input = null;
$recursive = false;
$engine = 'mysql';

$iter = new ArrayIterator(array_slice($argv, 1));
foreach ($iter as $v) {
	if ($v == '--bootstrap') {
		$iter->next();
		$bootstrap = $iter->current(); 
	}
	if ($v == '--engine') {
		$iter->next();
		$engine = $iter->current(); 
	}
	elseif ($v == '--recurse' || $v == '-r') {
		$recursive = true;
	}
	elseif (strpos($v, '--')===0 || $input) {
		echo "Invalid arguments\n\n".$usage; exit(1);
	}
	else {
		$input = $v;
	}
}

if (!$input) {
	echo "Input not specified\n\n".$usage; exit(1);
}
if (!file_exists($input)) {
	echo "Input file/folder did not exist\n\n".$usage; exit(1);
}
if ($bootstrap && !file_exists($bootstrap)) {
	echo "Bootstrap file did not exist\n\n".$usage; exit(1);
}

if ($bootstrap)
	require($bootstrap);

$defaultManager = new Amiss\Manager(new Amiss\Connector($engine.':blahblah'));
Amiss\Active\Record::setManager($defaultManager);

$toCreate = find_classes($input);

foreach ($toCreate as $class) {
	if (is_subclass_of($class, 'Amiss\Active\Record')) {
		$manager = $class::getMeta()->getManager();
		
		if ($class::$fields) {
			$builder = new Amiss\Active\TableBuilder($class);
			$create = $builder->buildCreateTableSql();
			if (!preg_match("/;\s*$/", $create))
				$create .= ';';
			echo $create.PHP_EOL.PHP_EOL;
		}
		else {
			echo "Warning: $class does not declare fields\n";
		}
	}
}
