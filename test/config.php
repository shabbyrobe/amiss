<?php
date_default_timezone_set('Australia/Melbourne');

require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../doc/demo/model.php';
require_once __DIR__.'/../doc/demo/ar.php';

autoload_namespace('Amiss\Test', __DIR__.'/lib');

function autoload_namespace($prefix, $path, $options=array())
{
    $defaults = [
        'prepend'     => false,  // stick the autoloader up the top of the stack rather than on the bottom
        'suffix'      => '.php', // attached to the last namespace segment to determine the class file name
        'stripPrefix' => true,   // remove the prefix from the start of the class name before generating path
        'separator'   => '\\',   // namespace separator (switch to _ for 5.2 style)
    ];
    $options = array_merge($defaults, $options);
    $prefix = trim($prefix, $options['separator']);
    
    spl_autoload_register(
        function($class) use ($prefix, $path, $options) {
            static $prefixLen = null;
            if ($prefixLen === null) {
                $prefixLen = strlen($prefix);
            }
            
            if (strpos($class, $prefix.$options['separator'])===0 || $class == $prefix) {
                $toSplit = $options['stripPrefix']  ? substr($class, $prefixLen) : $class;
                $file = str_replace('../', '', $path.'/'.str_replace($options['separator'], '/', $toSplit)).$options['suffix'];
                
                if (file_exists($file)) {
                    require $file;
                }
            }
        },
        null,
        $options['prepend']
    );
}

