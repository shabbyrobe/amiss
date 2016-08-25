<?php 
require_once __DIR__.'/config.php';
?>
<html>
<body>
<?php
$ex = isset($_SERVER['PATH_INFO']) ? trim($_SERVER['PATH_INFO'], '/') : null;
$files = [
    'ar.php',
    'model.php',
    'schema.sqlite.sql',
    'testdata.sql',
];
if (in_array($ex, $files)) {
    $extn = pathinfo($ex, PATHINFO_EXTENSION);
    $data = file_get_contents($amissPath.'/../doc/demo/'.$ex);
    if ($extn == 'sqlite') {
        echo "<pre>".$data."</pre>";
    }
    else {
        echo highlight_string($data, true);
    }
}
?>
</body>
</html>
