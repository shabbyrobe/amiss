<?php
$usage = <<<'DOCOPT'
Usage: amiss create-tables-sql [options] [--note=<note>]... 
                               [--ns=<ns>]... <input>

Emits SQL for creating active record tables

INPUT:
  PHP file or folder containing active records to create tables for

Options:
  --engine <engine>   Database engine (mysql, sqlite) [default: mysql]
  --bootstrap <file>  File to be run before creating tables
  --mapper <class>    Mapper class name. Either pass this or define a 
                      mapper in the boostrap
  --note <note>       Search for all classes that have this note set at 
                      class level 
  --ns <ns>           Search for all classes in this namespace. Can specify 
                      more than once. If only specified once, will be set as 
                      Amiss\Mapper\Base->objectNamespace

Examples:
Use all classes with the @foo annotation at class level:
    amiss create-tables-sql --note foo src/foo.php

Use all classes in the Foo\Model namespace in the src/foo.php file
    amiss create-tables-sql --ns Foo\\Model src/foo.php

Use all classes in the Foo\Model and Bar\Model namespaces with
the ":foo = {};" annotation in the current directory:
    amiss create-tables-sql --ns Foo\\Model \
        --ns Bar\\Model \
        --note foo -r .

DOCOPT;

$options = (new \Docopt\Handler)->handle($usage);
$mapper = new \Amiss\Mapper\Arrays;
$meta = new \Amiss\Meta('stdClass', [
    'fields'=>[
        'input'=>'<input>',
        'engine'=>'--engine',
        'bootstrap'=>'--bootstrap',
        'mapperClass'=>'--mapper',
        'notes'=>'--note',
        'namespaces'=>'--ns',
    ]
]);
$options = $mapper->toObject($options, null, $meta);

if (!$options->notes && !$options->namespaces) {
    echo "Please specify some notes and/or namespaces to search for\n\n".$usage; exit(1);
}
if (!$options->input) {
    echo "Input not specified\n\n".$usage; exit(1);
}
if (!file_exists($options->input)) {
    echo "Input file/folder did not exist\n\n".$usage; exit(1);
}
if ($options->bootstrap && !file_exists($options->bootstrap)) {
    echo "Bootstrap file did not exist\n\n".$usage; exit(1);
}

$mapper = null;
$manager = null;
$connector = null;
if ($options->bootstrap) {
    require($options->bootstrap);
}

if (!$mapper) {
    if (!$options->mapperClass) {
        $options->mapperClass = 'Amiss\Mapper\Note'; 
    }
    $mapper = new $options->mapperClass;
}
if (!$mapper) {
    echo "Please pass the --mapper parameter or define a mapper in a bootstrap file\n\n".$usage; exit(1);
}
if (!$manager) {
    $manager = new Amiss\Sql\Manager(new \PDOK\Connector($options->engine.':blahblah'), $mapper);
}

$toCreate = find_classes($options->input);
if ($options->namespaces) {
    $toCreate = filter_classes_by_namespaces($toCreate, $options->namespaces);
    if (count($options->namespaces) == 1 && $mapper instanceof \Amiss\Mapper\Base) {
        $mapper->objectNamespace = $options->namespaces[0];
    }
}
if ($options->notes) {
    $toCreate = filter_classes_by_notes($toCreate, $options->notes);
}

$sql = Amiss\Sql\TableBuilder::createSQL($options->engine, $mapper, $toCreate);
foreach ($sql as $class=>$queries) {
    foreach ($queries as $q) {
        echo $q."\n\n";
    }
}
