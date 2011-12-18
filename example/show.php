<?php

require_once('config.php');
$ex = isset($_SERVER['PATH_INFO']) ? trim($_SERVER['PATH_INFO'], '/') : null;
if (!$ex) exit;
$ex = str_replace('..', '', $ex);
if (strpos($ex, '/')===false) {
	exit;
}
$file = __DIR__.'/'.$ex.'.php';
require(dirname($file).'/config.php');

if (isset($_GET['run'])) {
	require($file);
	exit;
}

ob_start();
$startTime = microtime(true);
$data = require($file);
$timeTaken = microtime(true) - $startTime;
dump($data);
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
<dd><?php echo round($timeTaken * 1000, 4) ?>ms</dd>

<dt>Peak memory</dt>
<dd><?php echo memory_get_peak_usage() ?></dd>

<dt>Used memory</dt>
<dd><?php echo memory_get_usage() ?></dd>
</dl>

</body>
</html>
