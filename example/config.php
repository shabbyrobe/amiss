<?php
require_once __DIR__.'/../vendor/autoload.php';

date_default_timezone_set("UTC");

// temporary: preload classes to avoid autoloads distorting performance numbers
pre_autoload: {
    $iter = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator(__DIR__.'/../src'),
        \RecursiveIteratorIterator::LEAVES_ONLY);

    foreach ($iter as $item) {
        $name = $item->getFilename();
        if ($item->isDir() || $name[0] == '.' || $item->getExtension() != 'php') {
            continue;
        }
        require_once($item);    
    }
}

function e($val)
{
    return htmlspecialchars($val, ENT_QUOTES, 'UTF-8');
}

function source($code)
{
    ob_start();
    
    $lines = substr_count($code, "\n");
    echo '<table><tr><td class="lines">';
    for ($i=1; $i<=$lines; $i++) {
        echo '<span id="line-'.$i.'">'.$i.'</span><br />';
    }
    echo '</td><td class="code">';
    echo highlight_string($code, true);
    echo '</td></tr></table>';
    
    return ob_get_clean();
}

function dump_example($obj, $depth=10, $highlight=true)
{
    $trace = debug_backtrace();
    $line = $trace[0]['line'];
    
    echo '<div class="dump">';
    echo '<div class="code">';
    echo dump_highlight($obj, $depth);
    echo "</div>";
    echo '</div';
}

function get_note_cache()
{
    $type = isset($_GET['cache']) ? $_GET['cache'] : null;
    $active = $type == true;
    
    $cache = null;
    
    if ($active) {
        if ($type == 'hack') {
            $prefix = 'nca-';
            $path = sys_get_temp_dir();
            $cache = new \Amiss\Cache(
                function ($key) use ($path, $prefix) {
                    $key = md5($key);
                    $file = $path.'/'.$prefix.$key;
                    if (file_exists($file)) {
                        return unserialize(file_get_contents($file));
                    }
                },
                function ($key, $value) use ($path, $prefix) {
                    $key = md5($key);
                    $file = $path.'/'.$prefix.$key;
                    file_put_contents($file, serialize($value));
                }
            );
        }
        elseif ($type == 'xcache') {
            $cache = new \Amiss\Cache('xcache_get', 'xcache_set');
        }
        elseif ($type == 'apc') {
            $cache = new \Amiss\Cache('apc_fetch', 'apc_store');
        }
    }
    return $cache;
}

function titleise_slug($slug)
{
    return ucfirst(preg_replace('/[_-]/', ' ', $slug));
}

function dump_highlight($var, $depth=null)
{
    $out = dump($var);
    $out = highlight_string("<?php\n".$out, true);
    $out = preg_replace('@&lt;\?php<br />@s', '', $out, 1);
    return $out;
}
