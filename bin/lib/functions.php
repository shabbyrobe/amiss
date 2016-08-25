<?php

function determine_classes($file)
{
    $c = get_declared_classes();
    require_once($file);
    $diff = array_diff(get_declared_classes(), $c);
    return $diff;
}

function find_classes($input, $recursive=true)
{
    $classes = array();
    if (is_file($input)) {
        if (preg_match('/\.php$/', $input)) {
            $classes = determine_classes($input);
        }
    }
    elseif (is_dir($input)) {
        $iter = null;
        if ($recursive) {
            $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($input), RecursiveIteratorIterator::LEAVES_ONLY);
        }
        else {
            $iter = new DirectoryIterator($input);
        }
        foreach ($iter as $i) {
            if ($i->isFile()) {
                if (preg_match('/\.php$/', $i)) {
                    $found = determine_classes($i->getPathname());
                    $classes = array_merge($classes, $found);
                }
            }
        }
    }
    $classes = array_unique($classes);
    $found = array();
    foreach ($classes as $c) {
        $rc = new \ReflectionClass($c);
        if ($rc->isInstantiable()) {
            $found[] = $c;
        }
    }
    return $found;
}

function filter_classes_by_namespaces($classes, $namespaces)
{
    if (!is_array($namespaces))
        $namespaces = array($namespaces);
    
    $found = array();
    foreach ($classes as $c) {
        $rc = new \ReflectionClass($c);
        if (in_array($rc->getNamespaceName(), $namespaces)) {
            $found[] = $c;
        }
    }
    return $found;
}

function filter_classes_by_notes($classes, $notes)
{
    if (!is_array($notes)) {
        $notes = array($notes);
    }
    
    $parser = new \Nope\Parser();
    $found = array();
    foreach ($classes as $c) {
        $classNotes = $parser->parseClass(new \ReflectionClass($c));
        foreach ($notes as $k) {
            if (isset($classNotes->notes[$k])) {
                $found[] = $c;
            }
        }
    }
    return $found;
}

// with thanks to http://stackoverflow.com/questions/187736/command-line-password-prompt-in-php
function prompt_silent($prompt = "Enter Password:")
{
  if (preg_match('/^win/i', PHP_OS)) {
    $vbscript = sys_get_temp_dir() . 'prompt_password.vbs';
    file_put_contents(
      $vbscript, 'wscript.echo(InputBox("'
      . addslashes($prompt)
      . '", "", "password here"))');
    $command = "cscript //nologo " . escapeshellarg($vbscript);
    $password = rtrim(shell_exec($command));
    unlink($vbscript);
    return $password;
  } else {
    $command = "/usr/bin/env bash -c 'echo OK'";
    if (rtrim(shell_exec($command)) !== 'OK') {
      trigger_error("Can't invoke bash");
      return;
    }
    $command = "/usr/bin/env bash -c 'read -s -p \""
      . addslashes($prompt)
      . "\" mypassword && echo \$mypassword'";
    $password = rtrim(shell_exec($command));
    echo "\n";
    return $password;
  }
}
