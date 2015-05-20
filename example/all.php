<?php
$parts = parse_url($_SERVER['SCRIPT_NAME']);
$webBase = dirname($parts['path']);

$iter = isset($_GET['cnt']) ? $_GET['cnt'] : 1;
$cache = isset($_GET['cache']) ? $_GET['cache'] : '';

$result = array();

for ($i = 0; $i < $iter; $i++) {
    foreach (array('active', 'array', 'note') as $folder) {
        foreach (new \DirectoryIterator(__DIR__.'/'.$folder) as $item) {
            if ($item->isDir() || $item->isDot())
                continue;
            if ($item->getFilename() == 'config.php')
                continue;
            
            
            $name = $folder.'/'.pathinfo($item, PATHINFO_FILENAME);
            $loop = isset($_GET['loop']) ? $_GET['loop'] : 1;
            $url = "{$webBase}/show.php/{$name}?cache={$cache}&loop={$loop}";
            $out = file_get_contents("http://localhost{$url}&fmt=json");
            $json = json_decode($out, true);
            
            if (!isset($result[$json['id']])) {
                $result[$json['id']] = (object)array(
                    'url'=>$url,
                    'count'=>0,
                    'queries'=>0,
                    'timeTakenMs'=>0,
                    'timeTakenMsMin'=>null,
                    'timeTakenMsMax'=>null,
                    'memUsed'=>0,
                    'memPeak'=>0,
                );
            }
            
            $current = &$result[$json['id']];
            $current->count++;
            $current->queries = $json['queries'];
            $current->timeTakenMs += $json['timeTakenMs'];
            
            if ($current->timeTakenMsMin === null || $json['timeTakenMs'] < $current->timeTakenMsMin)
                $current->timeTakenMsMin = $json['timeTakenMs'];
            if ($json['timeTakenMs'] > $current->timeTakenMsMax)
                $current->timeTakenMsMax = $json['timeTakenMs'];
            
            $current->memUsed += $json['memUsed'];
            $current->memPeak += $json['memPeak'];
        }
    }
}

foreach ($result as $id=>$data) {
    $data->timeTakenMs = $data->timeTakenMs / $data->count;
    $data->memUsed = $data->memUsed / $data->count;
    $data->memPeak = $data->memPeak / $data->count;
}

?>
<table border="2">
<tr>
    <th>Script</th>
    <th>-</th>
    <?php foreach ($data as $k=>$v): ?>
    <?php if ($k != 'url'): ?>
    <th><?= $k ?></th>
    <?php endif; ?>
    <?php endforeach; ?>
</tr>
<?php foreach ($result as $id=>$data): ?>
<tr>
    <th style="text-align:left;"><a href="<?= $data->url ?>"><?= $id ?></a></th>
    <td><a href="<?= $data->url ?>&fmt=json&loop=100&XDEBUG_PROFILE=1">P</a></td>
    <?php foreach ($data as $k=>$v): ?>
    <?php if ($k != 'url'): ?>
    <td><?= $v ?></td>
    <?php endif; ?>
    <?php endforeach; ?>
</tr>
<?php endforeach; ?>
</table>
