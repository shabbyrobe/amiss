<?php
$basePath = __DIR__.'/../';
$testPath = __DIR__;

require_once 'PHPUnit/Autoload.php';
require $testPath.'/config.php';

$options = array(
    'coverage-html'=>null,
    'filter'=>null,
    'exclude-group'=>null,
    'group'=>null,
);
$options = array_merge(
    $options,
    getopt('', array('no-sqlite', 'mysql', 'filter:', 'coverage-html:', 'exclude-group:', 'group:'))
);
$testMysql = array_key_exists('mysql', $options);

$config = array();
if ($testMysql) {
    $config = amisstest_config_load();
}

$groups = $options['group'] ? explode(',', $options['group']) : null;
$args = array(
    'reportDirectory'=>$options['coverage-html'],
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

$suite = new PHPUnit_Framework_TestSuite();

if (!array_key_exists('no-sqlite', $options)) {
    $sqliteSuite = new DatabaseSuite(array(
        'engine'=>'sqlite',
        'dsn'=>'sqlite::memory:',
    ));
    suite_add_dir($sqliteSuite, $testPath.'/unit/');
    suite_add_dir($sqliteSuite, $testPath.'/acceptance/');
    suite_add_dir($sqliteSuite, $testPath.'/cookbook/');
    $suite->addTest($sqliteSuite);
}

if ($testMysql) {
    if (!isset($config['mysql']))
        throw new \Exception("Missing [mysql] section in amisstestrc file");
    
    $mysqlSuite = new DatabaseSuite(array(
        'engine'=>'mysql',
        'dsn'=>'mysql:host='.$config['mysql']['host'],
        'user'=>$config['mysql']['user'],
        'password'=>$config['mysql']['password'],
        'dbName'=>'amiss_test_'.time(),
    ));
    suite_add_dir($mysqlSuite, $testPath.'/unit/');
    suite_add_dir($mysqlSuite, $testPath.'/acceptance/');
    suite_add_dir($mysqlSuite, $testPath.'/cookbook/');
    $suite->addTest($mysqlSuite);
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
    global $testPath;
    
    $paths = array(
        $testPath."/amisstestrc",
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
        return parse_ini_file($found, true);
    }
    else {
        die("Please place a file called amisstestrc in the root of this project, or at ~/.amisstestrc.\n"
            ."It should be an ini file with a [mysql] section containing the following keys: host, user, password\n"
        );
    }
}

