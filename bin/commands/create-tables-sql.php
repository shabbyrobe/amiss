<?php

require_once(__DIR__.'/../../src/Loader.php');
require_once(__DIR__.'/../lib/functions.php');
Amiss\Loader::register();

$usage = "amiss create-tables-sql [OPTIONS] INPUT

Emits SQL for creating active record tables

INPUT:
  PHP file or folder containing active records to create tables for

Options:
  --engine        Database engine (mysql, sqlite)
  --bootstrap     File to be run before creating tables
  -r, --recurse   Recurse all input directories looking for items
  --mapper        Mapper class name. Either pass this or define a mapper in the boostrap
  --note NOTE     Search for all classes that have this note set at class level 
  --ns NAMESPACE  Search for all classes in this namespace. Can specify more than once.

Examples:
Use all classes with the @foo annotation at class level:
  amiss create-tables-sql --note foo

Use all classes in the Foo\Model namespace
  amiss create-tables-sql --namespace Foo\\Model

Use all classes in the Foo\Model and Bar\Model namespaces with the @foo annotation:
  amiss create-tables-sql --namespace Foo\\Model --namespace Bar\\Model --note foo

";

$bootstrap = null;
$input = null;
$recursive = false;
$engine = 'mysql';
$namespaces = array();
$notes = array();
$mapperClass = null;

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
    elseif ($v == '--engine') {
        $iter->next();
        $engine = $iter->current(); 
    }
    elseif ($v == '--recurse' || $v == '-r') {
        $recursive = true;
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
        echo "Invalid arguments\n\n".$usage; exit(1);
    }
    else {
        $input = $v;
    }
}

if (!$notes && !$namespaces) {
    echo "Please specify some notes and/or namespaces to search for\n\n".$usage; exit(1);
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

if (!$manager)
    $manager = new Amiss\Sql\Manager(new Amiss\Sql\Connector($engine.':blahblah'), $mapper);

$toCreate = find_classes($input);
if ($namespaces)
    $toCreate = filter_classes_by_namespaces($toCreate, $namespaces);
if ($notes)
    $toCreate = filter_classes_by_notes($toCreate, $notes);

foreach ($toCreate as $class) {
    $builder = new Amiss\Sql\TableBuilder($manager, $class);
    $create = $builder->buildCreateTableSql();
    if (!preg_match("/;\s*$/", $create))
        $create .= ';';
    echo $create.PHP_EOL.PHP_EOL;
}
