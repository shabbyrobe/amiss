<?php
$usage = <<<'DOCOPT'
Creates tables in the specified database

Usage: amiss create-tables [options] [--note=<note>]... [--ns=<ns>]...
                           <input>

<input>:
  PHP file or folder containing active records to create tables for

Options:
  --dsn <dsn>              Database DSN to use.
  --db <db>                Database name (created if it doesn't exist)
  -u, --user <user>        Database user
  -p                       Prompt for database password 
  --password <password>    Database password (don't use this - use -p instead) 
  --bootstrap <bootstrap>  File to be run before creating tables
  --mapper <mapper>        Mapper class name. Either pass this or define a mapper 
                           in the boostrap
  --note <note>            Search for all classes that have this note set at 
                           class level. Can specify more than once.
  --ns <ns>                Search for all classes in this namespace. Can specify more 
                           than once.

Examples:
Use all classes with the @foo annotation at class level:
    amiss create-tables --note foo

Use all classes in the Foo\Model namespace
    amiss create-tables --ns Foo\\Model

Use all classes in the Foo\Model and Bar\Model namespaces with the 
annotation ":foo = {}":
    amiss create-tables --ns Foo\\Model --ns Bar\\Model --note foo

DOCOPT;

$options = (new \Docopt\Handler)->handle($usage);
$optMapper = new \Amiss\Mapper\Arrays;
$meta = new \Amiss\Meta('stdClass', [
    'fields'=>[
        'input'=>'<input>',
        'dsn'=>'--dsn',
        'db'=>'--db',
        'prompt'=>'-p',
        'user'=>'--user',
        'password'=>'--password',
        'bootstrap'=>'--bootstrap',
        'mapperClass'=>'--mapper',
        'notes'=>'--note',
        'namespaces'=>'--ns',
    ]
]);
$options = $optMapper->toObject($options, null, $meta);

if (!$options->input) {
    echo "Input not specified\n\n";
    echo $usage;
    exit(1);
}

if (!$options->dsn) {
    echo "DSN not specified\n\n";
    echo $usage;
    exit(1);
}

if (!file_exists($options->input)) {
    echo "Input file/folder did not exist\n\n";
    echo $usage;
    exit(1);
}

if ($options->bootstrap && !file_exists($options->bootstrap)) {
    echo "Bootstrap file did not exist\n\n";
    echo $usage;
    exit(1);
}

if (!$options->notes && !$options->namespaces) {
    echo "Please specify some notes and/or namespaces to search for\n\n";
    echo $usage;
    exit(1);
}

if ($options->prompt) {
    $options->password = prompt_silent("Password: ");
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

if (!$connector) {
    $connector = new \PDOK\Connector($options->dsn, $options->user, $options->password);
}

if ($connector->engine != 'sqlite') {
    if (!$options->db) {
        echo "DB not specified\n\n";
        echo $usage;
        exit(1);
    }
    $connector->execute("CREATE DATABASE IF NOT EXISTS ".$connector->quoteIdentifier($options->db));
    $connector->execute("USE ".$connector->quoteIdentifier($options->db));
}

$toCreate = find_classes($options->input);
if ($options->namespaces) {
    $toCreate = filter_classes_by_namespaces($toCreate, $options->namespaces);
}
if ($options->notes) {
    $toCreate = filter_classes_by_notes($toCreate, $options->notes);
}

$sql = Amiss\Sql\TableBuilder::createSQL($connector->engine, $mapper, $toCreate);

foreach ($sql as $class=>$queries) {
    foreach ($queries as $q) {
        $connector->execute($q);
    }
}
