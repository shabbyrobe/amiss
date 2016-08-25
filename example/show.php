<?php
/**
 * "What's with all the 'eval' and '.phps' and stuff?"
 *
 * Good question. The code in this folder is pure garbage, but it's a useful
 * demo. It should, however, NEVER EVER be run on a production server.
 *
 * The examples themselves (the stuff in the 'active', 'note', etc directories)
 * need to be displayed in a nice way in this utility so rather than have them
 * stored as .php files and have to fill them full of guard clauses, we can
 * give them a different extension and eval them in here. It doesn't matter
 * if they're publicly visible because they're publicly visible in github anyway.
 */
require_once __DIR__.'/config.php';

function fix_script($script)
{
    $script = preg_replace('/^<\?php/', '', $script);
    return $script;
}

if (php_sapi_name() == 'cli') {
    $fmt = 'json';
    $options = getopt('f:c:l:');
    $ex = $options['f'];
    if (isset($options['l'])) {
        $_GET['loop'] = $options['l'];
    }
    if (array_key_exists('c', $options)) {
        $_GET['cache'] = $options['c'];
    }
}
else {
    $ex = isset($_SERVER['PATH_INFO']) ? trim($_SERVER['PATH_INFO'], '/') : null;
    $fmt = isset($_GET['fmt']) ? $_GET['fmt'] : null;
}

if (!$ex) exit;
$ex = str_replace('..', '', $ex);
if (strpos($ex, '/')===false) {
    exit;
}
$file = __DIR__.'/'.$ex.'.phps';
eval(fix_script(file_get_contents(dirname($file).'/config.phps')));

if (!in_array($fmt, array('html', 'json'))) {
    $fmt = 'html';
}

if (isset($_GET['run'])) {
    require($file);
    exit;
}

$rawScript = file_get_contents($file);
$script = fix_script($rawScript);
if (isset($_GET['loop'])) {
    for ($i=0; $i<$_GET['loop']-1; $i++) {
        eval($script);
    }
}

$manager->queries = 0;
ob_start();
$startTime = microtime(true);
$data = eval($script);
$timeTaken = microtime(true) - $startTime;
$timeTaken = round($timeTaken * 1000, 4);
$memUsed = memory_get_usage();
$memPeak = memory_get_peak_usage();

if ($fmt == 'html'):
dump_example($data);
$output = ob_get_clean();
$source = source($rawScript, true);
?>
<html>
<head>
<style type="text/css">
.lines, .code {
    font-size:12px;
    font-family:Courier;
}
.lines {
    width:10px;
    padding-right:4px;
}
</style>
</head>
<body>
<a href="<?php echo rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/index.php">Back to index</a>
<h2>Source</h2>
<div>
<?php echo $source; ?>
</div>

<h2>Output</h2>
<div>
<?php echo $output; ?>
</div>

<dl>
<dt>Queries</dt>
<dd><?php echo $manager->queries ?></dd>

<dt>Time taken</dt>
<dd id="time-taken"><?php echo $timeTaken ?>ms</dd>

<dt>Peak memory</dt>
<dd id="peak-mem"><?php echo $memPeak ?></dd>

<dt>Used memory</dt>
<dd id="used-mem"><?php echo $memUsed ?></dd>
</dl>

</body>
</html>
<?php elseif ($fmt == 'json'):
echo json_encode(array(
    'id'=>$ex,
    'timeTakenMs'=>$timeTaken,
    'queries'=>$manager->queries,
    'memUsed'=>$memUsed,
    'memPeak'=>$memPeak,
));
endif;
