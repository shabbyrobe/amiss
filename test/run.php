<?php
$usage = "Amiss test runner
Usage: test/run.php [--no-sqlite] [--with-mysql] [--filter=<expr>]
           [--coverage-html=<outpath>] [--exclude-group=<group>]
           [--group=<group>]
";

$basePath = __DIR__.'/../';
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
    getopt('', array('help', 'no-sqlite', 'with-mysql', 'with-pgsql', 'filter:', 'coverage-html:', 'exclude-group:', 'group:'))
);
$testMysql = array_key_exists('with-mysql', $options);
$testPgsql = array_key_exists('with-pgsql', $options);
$help = array_key_exists('help', $options);
if ($help) {
    echo $usage;
    exit;
}

$config = array();
if ($testMysql || $testPgsql) {
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

if ($options['coverage-html']) {
    $args['coverageHtml'] = $options['coverage-html'];
}

$sqliteConnection = array(
    'engine'=>'sqlite',
    'dsn'=>'sqlite::memory:',
);
TestApp::instance()->connectionInfo = $sqliteConnection;

$suite = new PHPUnit_Framework_TestSuite();
suite_add_dir($suite, $testPath.'/unit/');
suite_add_dir($suite, $testPath.'/cookbook/');

if (!array_key_exists('no-sqlite', $options)) {
    $sqliteSuite = new DatabaseSuite($sqliteConnection);
    suite_add_dir($sqliteSuite, $testPath.'/acceptance/');
    $suite->addTest($sqliteSuite);
}

if ($testMysql) {
    if (!isset($config['mysql']))
        throw new \Exception("Missing [mysql] section in amisstestrc file");
    
    $mysqlSuite = new DatabaseSuite(array(
        'engine'=>'mysql',
        'dsn'=>"mysql:host={$config['mysql']['host']};port={$config['mysql']['port']}",
        'user'=>$config['mysql']['user'],
        'password'=>$config['mysql']['password'],
        'dbName'=>'amiss_test_'.time(),
    ));
    suite_add_dir($mysqlSuite, $testPath.'/acceptance/');
    $suite->addTest($mysqlSuite);
}

if ($testPgsql) {
    if (!isset($config['pgsql']))
        throw new \Exception("Missing [pgsql] section in amisstestrc file");
    
    $parts = [];
    if ($config['pgsql']['host'])
        $parts[] = "host=".$config['pgsql']['host'];
    if ($config['pgsql']['port'])
        $parts[] = "port=".$config['pgsql']['port'];

    $parts[] = "dbname=amiss_test";

    $pgsqlSuite = new DatabaseSuite(array(
        'engine'=>'pgsql',
        'dsn'=>"pgsql:".implode(';', $parts),
        'user'=>$config['pgsql']['user'],
        'password'=>$config['pgsql']['password'],
        'dbName'=>'amiss_test_'.time(),
    ));
    suite_add_dir($pgsqlSuite, $testPath.'/acceptance/');
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

    if (!preg_match("/Test\.php$/", $file))
        return array();

    $file = realpath($file);
    if (isset($cache[$file]))
        return $cache[$file];

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

