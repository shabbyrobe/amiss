<?php
require_once __DIR__.'/../vendor/autoload.php';

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
    echo '<div class="file">Dump at <a href="#line-'.$line.'">Line '.$line.'</a>:</div>';
    echo '<div class="code">';
    echo dump_highlight($obj, $depth);
    echo "</div>";
    echo '</div';
}

function extract_file_metadata($file)
{
    $tokens = token_get_all(file_get_contents($file));
    $doc = null;
    foreach ($tokens as $token) {
        if ($token[0] == T_DOC_COMMENT) {
            $doc = $token[1];
            break;
        }
    }
    
    $meta = array('title'=>'', 'description'=>'', 'notes'=>array());
    if ($doc) {
        $lines = preg_split("/(\r\n|\n)/", trim(trim($doc, '/*')));
        foreach ($lines as $k=>$line) $lines[$k] = preg_replace('/^[\t ]*\* /', '', $line);
        $meta['title'] = $lines[0];
        
        $notes = false;
        foreach (array_slice($lines, 1) as $line) {
            $test = trim($line);
            if ($test && $test[0] == '@') {
                $notes = true;
            }
            
            if ($notes) {
                $x = explode(' ', ltrim($line, ' @'));
                if ($x[0]) {
                    $meta['notes'][$x[0]] = isset($x[1]) ? $x[1] : true;
                } 
            }
            else {
                $meta['description'] .= $line;
            }
        }
    }
    return $meta;
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

function dump($var, $depth=null)
{
    if ($depth === null) $depth = 3;

    static $indent = 0;
    static $objects = array();
    static $ocnt = 0;
    static $spaces = 4;

    $out = '';
    $type = gettype($var);
    switch ($type) {
        case 'NULL':
            $out .= 'null';
        break;

        case 'integer':
            $out .= $var;
        break;

        case 'string':
            $out .= "'".addslashes($var)."'";
        break;

        case 'double':
            $out .= $var.'D';
        break;

        case 'boolean':
            $out .= $var ? 'true' : 'false';
        break;

        case 'resource':
            $out .= '[resource: '.get_resource_type($var).']';
        break;

        case 'array':
        case 'object':
            $obj = $type == 'object';
            $name = $obj ? get_class($var) : 'array';
            $hash = $obj ? spl_object_hash($var) : null;

            ++ $indent;

            if ($indent >= $depth) {
                $out .= $name.' (...)';
            }
            elseif (!$var) {
                $out .= $name.' ()';
            }
            elseif (isset($objects[$hash])) {
                $out .= $name.'#'.$objects[$hash].' (...)';
            }
            else {
                if ($obj) {
                    $fmt = '[%s]';
                    $objects[$hash] = ++$ocnt;
                    $name .= '#'.$ocnt;
                }
                else $fmt = "'%s'";

                $margin = str_repeat(' ', $indent * $spaces);
                $out .= $name." (\n";
                foreach ((array)$var as $k=>$v) {
                    $k = str_replace("\0", ':', trim($k));
                    $out .= $margin.sprintf($fmt, $k)." => ".dump($v, $depth)."\n";
                }
                $out .= str_repeat(' ', ($indent - 1) * $spaces).")";
            }
            -- $indent;
        break;
    }

    if ($indent == 0) {
        $objects = array();
        $ocnt = 0;
        $out .= "\n";
    }

    return $out;
}
