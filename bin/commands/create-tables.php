<?php

require_once(__DIR__.'/../../src/Loader.php');
require_once(__DIR__.'/../lib/functions.php');
Amiss\Loader::register();

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
  --mapper        Mapper class name. Either pass this or define a mapper in the boostrap
  --note NOTE     Search for all classes that have this note set at class level 
  --ns NAMESPACE  Search for all classes in this namespace. Can specify more than once.

Examples:
Use all classes with the @foo annotation at class level:
  amiss create-tables --note foo

Use all classes in the Foo\Model namespace
  amiss create-tables --namespace Foo\\Model
	
Use all classes in the Foo\Model and Bar\Model namespaces with the @foo annotation:
  amiss create-tables --namespace Foo\\Model --namespace Bar\\Model --note foo

";

$bootstrap = null;
$input = null;
$dsn = null;
$prompt = false;
$user = null;
$password = null;
$recursive = false;
$namespaces = array();
$mapperClass = null;
$notes = array();

$iter = new ArrayIterator(array_slice($argv, 1));
foreach ($iter as $v) {
	if ($v == '--bootstrap') {
		$iter->next();
		$bootstrap = $iter->current(); 
	}
	elseif ($v == '--mapper') {
		$iter->next();
		$mapperClass = $iter->current(); 
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
	elseif ($v == '--namespace') {
		$iter->next();
		$namespaces[] = $iter->current();
	}
	elseif ($v == '--note') {
		$iter->next();
		$notes[] = $iter->current();
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

if (!$notes && !$namespaces) {
	echo "Please specify some notes and/or namespaces to search for\n\n";
	echo $usage;
	exit(1);
}

if ($prompt) {
	$password = prompt_silent("Password: ");
}

$mapper = null;
$manager = null;
$connector = null;
if ($bootstrap) require($bootstrap);

if (!$mapper) {
	if (!$mapperClass) $mapperClass = 'Amiss\Mapper\Note'; 
	$mapper = new $mapperClass;
}

if (!$mapper) {
	echo "Please pass the --mapper parameter or define a mapper in a bootstrap file\n\n".$usage; exit(1);
}

if (!$connector)
	$connector = new Amiss\Connector($dsn, $user, $password);

if (!$manager)
	$manager = new Amiss\Manager(new Amiss\Connector($engine.':blahblah'), $mapper);

$toCreate = find_classes($input);
if ($namespaces)
	$toCreate = filter_classes_by_namespaces($toCreate, $namespaces);
if ($notes)
	$toCreate = filter_classes_by_notes($toCreate, $notes);

foreach ($toCreate as $class) {
	$builder = new Amiss\TableBuilder($manager, $class);
	$builder->createTable();
}
