<?php

require_once(__DIR__.'/../../src/Loader.php');
require_once(__DIR__.'/../lib/functions.php');
spl_autoload_register(array(new Amiss\Loader, 'load'));

$usage = "amiss create-tables [OPTIONS] INPUT

Creates tables in the specified database

INPUT:
  PHP file or folder containing active records to create tables for

Options:
  --dsn           Database DSN to use.
  -u, --user      Database user
  -p              Prompt for database password 
  --password      Database password (don't use this - use -p instead) 
  --bootstrap     File to be run before creating tables
  -r, --recurse   Recurse all input directories looking for active records
";

$bootstrap = null;
$input = null;
$dsn = null;
$prompt = false;
$user = null;
$password = null;
$recursive = false;

$iter = new ArrayIterator(array_slice($argv, 1));
foreach ($iter as $v) {
	if ($v == '--bootstrap') {
		$iter->next();
		$bootstrap = $iter->current(); 
	}
	elseif ($v == '--recurse' || $v == '-r') {
		$recursive = true;
	}
	elseif ($v == '--user' || $v == '-u') {
		$iter->next();
		$user = $iter->current();
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
	elseif (strpos($v, '--')===0 || $input) {
		echo "Invalid arguments\n\n";
		echo $usage;
		exit(1);
	}
	else {
		$input = $v;
	}
}

if (!$input) {
	echo "Input not specified\n\n";
	echo $usage;
	exit(1);
}

if (!$dsn) {
	echo "DSN not specified\n\n";
	echo $usage;
	exit(1);
}

if (!file_exists($input)) {
	echo "Input file/folder did not exist\n\n";
	echo $usage;
	exit(1);
}

if ($bootstrap && !file_exists($bootstrap)) {
	echo "Bootstrap file did not exist\n\n";
	echo $usage;
	exit(1);
}

if ($bootstrap)
	require($bootstrap);

$connector = new Amiss\Connector($dsn, $user, $password);
Amiss\Active\Record::setManager(new Amiss\Manager($connector));

$toCreate = find_classes($input);

foreach ($toCreate as $class) {
	if (is_subclass_of($class, 'Amiss\Active\Record')) {
		$manager = $class::getManager();
		if (!$manager) {
			$class::setManager($defaultManager);
		}
		if ($class::$fields) {
			$builder = new Amiss\TableBuilder($manager, $class);
			$builder->createTable();
		}
		else {
			echo "Warning: $class does not declare fields\n";
		}
	}
}
