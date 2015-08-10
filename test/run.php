<?php
$usage = "Amiss test runner
Usage: test/run.php [--no-sqlite] [--with=<with>] [--filter=<expr>]
           [--coverage-html=<outpath>] [--exclude-group=<group>]
           [--group=<group>]

With: mysql, mysqlp (persistent)
";

$basePath = __DIR__.'/../';
define('AMISS_BASE_PATH', $basePath);
$testPath = __DIR__;

require $testPath.'/config.php';

$options = array(
    'coverage-html'=>null,
    'filter'=>null,
    'exclude-group'=>null,
    'group'=>null,
);
$options = array_merge(
    $options,
    getopt('', array('help', 'no-sqlite', 'with:', 'filter:', 'coverage-html:', 'exclude-group:', 'group:'))
);
$with = array_key_exists('with', $options) ? explode(',', $options['with']) : [];
$help = array_key_exists('help', $options);
if ($help) {
    echo $usage;
    exit;
}

if ($with == ['all']) {
    $with = ['mysql', 'mysqlp'];
}

$config = array();
if ($with) {
    $config = amisstest_config_load();
}

$groups = $options['group'] ? explode(',', $options['group']) : null;
$args = array(
    'filter'=>$options['filter'],
    'excludeGroups'=>explode(',', $options['exclude-group']),
    'groups'=>$groups,
    'strict'=>true,
    'processIsolation'=>false,
    'backupGlobals'=>false,
    'backupStaticAttributes'=>false,
    'convertErrorsToExceptions'=>true,
    'convertNoticesToExceptions'=>true,
    'convertWarningsToExceptions'=>true,
    'addUncoveredFilesFromWhitelist'=>true,
    'processUncoveredFilesFromWhitelist'=>true,
);

if (getenv('CAPER_RUN')) {
    $args['listeners'] = [new \Caper\PHPUnit\Listener];
}

if ($options['coverage-html']) {
    $args['coverageHtml'] = $options['coverage-html'];
}

$sqliteConnection = array(
    'engine'=>'sqlite',
    'dsn'=>'sqlite::memory:',
);
\Amiss\Test\Helper\Env::instance()->connectionInfo = $sqliteConnection;

$suite = new PHPUnit_Framework_TestSuite();
suite_add_dir($suite, $testPath.'/lib/Unit/');
suite_add_dir($suite, $testPath.'/lib/Cookbook/');

if (!array_key_exists('no-sqlite', $options)) {
    $sqliteSuite = new \Amiss\Test\Helper\DatabaseSuite($sqliteConnection);
    suite_add_dir($sqliteSuite, $testPath.'/lib/Acceptance/');
    $suite->addTest($sqliteSuite);
}

if (in_array('mysql', $with)) {
    if (!isset($config['mysql'])) {
        throw new \Exception("Missing [mysql] section in amisstestrc file");
    }

    $mysqlSuite = new \Amiss\Test\Helper\DatabaseSuite(array(
        'engine'=>'mysql',
        'dsn'=>"mysql:host={$config['mysql']['host']};port={$config['mysql']['port']}",
        'user'=>$config['mysql']['user'],
        'password'=>$config['mysql']['password'],
        'dbName'=>'amiss_test_'.time(),
    ));
    suite_add_dir($mysqlSuite, $testPath.'/lib/Acceptance/');
    $suite->addTest($mysqlSuite);
}

if (in_array('mysqlp', $with)) {
    if (!isset($config['mysql'])) {
        throw new \Exception("Missing [mysql] section in amisstestrc file");
    }

    $mysqlPersistentSuite = new \Amiss\Test\Helper\DatabaseSuite(array(
        'engine'=>'mysql',
        'dsn'=>"mysql:host={$config['mysql']['host']};port={$config['mysql']['port']}",
        'user'=>$config['mysql']['user'],
        'password'=>$config['mysql']['password'],
        'dbName'=>'amiss_test_'.time(),
        'options'=>[\PDO::ATTR_PERSISTENT => true],
    ));
    suite_add_dir($mysqlPersistentSuite, $testPath.'/lib/Acceptance/');
    $suite->addTest($mysqlPersistentSuite);
}

if (in_array('pgsql', $with)) {
    if (!isset($config['pgsql'])) {
        throw new \Exception("Missing [pgsql] section in amisstestrc file");
    }
    
    $parts = [];
    if ($config['pgsql']['host']) {
        $parts[] = "host=".$config['pgsql']['host'];
    }
    if ($config['pgsql']['port']) {
        $parts[] = "port=".$config['pgsql']['port'];
    }

    $parts[] = "dbname=amiss_test";

    $pgsqlSuite = new \Amiss\Test\Helper\DatabaseSuite(array(
        'engine'=>'pgsql',
        'dsn'=>"pgsql:".implode(';', $parts),
        'user'=>$config['pgsql']['user'],
        'password'=>$config['pgsql']['password'],
        'dbName'=>'amiss_test_'.time(),
    ));
    suite_add_dir($pgsqlSuite, $testPath.'/lib/Acceptance/');
    $suite->addTest($pgsqlSuite);
}

$filter = new PHP_CodeCoverage_Filter();
$filter->addDirectoryToWhitelist($basePath.'/src/', '.php');

$runner = new PHPUnit_TextUI_TestRunner(null, $filter);
$runner->doRun($suite, $args);

function suite_add_dir($suite, $dir)
{
    foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir), \RecursiveIteratorIterator::LEAVES_ONLY) as $item) {
        foreach (require_tests($item) as $class) {
            $suite->addTest(new PHPUnit_Framework_TestSuite($class));
        }
    }
}

function require_tests($file)
{
    static $cache = array();

    if (!preg_match("/Test\.php$/", $file)) {
        return array();
    }

    $file = realpath($file);
    if (isset($cache[$file])) {
        return $cache[$file];
    }

    $prevClasses = get_declared_classes();
    require $file;
    $nowClasses = get_declared_classes();

    $tests = array_diff($nowClasses, $prevClasses);
    $found = array();
    foreach ($tests as $class) {
        if (preg_match("/Test$/", $class)) {
            $found[] = $class;
        }
    }
    $cache[$file] = $found;

    return $found;
}

function amisstest_config_load()
{
    global $testPath, $basePath;

    $paths = array(
        $testPath."/amisstestrc",
        $basePath."/amisstestrc",
    );
    if (isset($_SERVER['HOME'])) {
        $paths[] = $_SERVER['HOME']."/.amisstestrc";
    }

    $found = null;
    foreach ($paths as $path) {
        $path = realpath($path);
        if ($path && file_exists($path)) {
            $found = $path;
            break;
        }
    }

    if ($found) {
        $ini = parse_ini_file($found, true);
        $ini['mysql'] = array_merge(array('port'=>3306), $ini['mysql']);
        return $ini;
    }
    else {
        die("Please place a file called amisstestrc in the root of this project, or at ~/.amisstestrc.\n"
            ."It should be an ini file with a [mysql] section containing the following keys: host, user, password\n"
        );
    }
}

