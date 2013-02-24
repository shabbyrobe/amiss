<?php

require_once(__DIR__.'/config.php');

if (php_sapi_name() == 'cli') {
    $ex = $argv[1];
    $fmt = 'json';
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
$file = __DIR__.'/'.$ex.'.php';
require(dirname($file).'/config.php');

if (!in_array($fmt, array('html', 'json')))
	$fmt = 'html';

if (isset($_GET['run'])) {
    require($file);
    exit;
}

if (isset($_GET['loop'])) {
    for ($i=0; $i<$_GET['loop']-1; $i++) {
        require($file);
    }
}

ob_start();
$startTime = microtime(true);
$data = require($file);
$timeTaken = microtime(true) - $startTime;
$timeTaken = round($timeTaken * 1000, 4);
$memUsed = memory_get_usage();
$memPeak = memory_get_peak_usage();

if ($fmt == 'html'):
dump_example($data);
$output = ob_get_clean();
$source = source(file_get_contents($file), true);
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
<a href="<?php echo dirname($_SERVER['SCRIPT_NAME']) ?>/index.php">Back to index</a>
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
