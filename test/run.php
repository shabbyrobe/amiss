<?php
$basePath = __DIR__.'/../';
$testPath = __DIR__;

require_once 'PHPUnit/Autoload.php';
require $testPath.'/config.php';

$options = array(
    'coverage-html'=>null,
    'filter'=>null,
    'exclude-group'=>null,
);
$options = array_merge(
    $options,
    getopt('', array('filter:', 'coverage-html:', 'exclude-group:'))
);

$suite = new PHPUnit_Framework_TestSuite();
suite_add_dir($suite, $testPath.'/unit/');
suite_add_dir($suite, $testPath.'/acceptance/');
suite_add_dir($suite, $testPath.'/cookbook/');

//$suite->addTest(new PHPUnit_Framework_TestSuite('Docopt\Test\PythonPortedTest'));
//$suite->addTest(Docopt\Test\LanguageAgnosticTest::createSuite($pyTestFile));

$filter = new PHP_CodeCoverage_Filter();
$filter->addDirectoryToWhitelist($basePath.'/src/', '.php');

$runner = new PHPUnit_TextUI_TestRunner(null, $filter);
$runner->doRun($suite, array(
    'reportDirectory'=>$options['coverage-html'],
    'filter'=>$options['filter'],
    'excludeGroups'=>explode(',', $options['exclude-group']),
    'strict'=>true,
    'processIsolation'=>false,
    'backupGlobals'=>false,
    'backupStaticAttributes'=>false,
    'convertErrorsToExceptions'=>true,
    'convertNoticesToExceptions'=>true,
    'convertWarningsToExceptions'=>true,
    'addUncoveredFilesFromWhitelist'=>true,
    'processUncoveredFilesFromWhitelist'=>true,
));

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
    if (!preg_match("/Test\.php$/", $file))
        return array();

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
    return $found;
}

