<?php
require __DIR__.'/../vendor/autoload.php';

$docPath = __DIR__.'/../doc';

list ($groups, $tests, $unclaimedBlocks, $allBlocks) = doctest_find($docPath);

$config = [
    'php' => [
        'bootstrap' => [
            __DIR__.'/../vendor/autoload.php',
        ],
    ],
];

$testInfo = [
    'php' => [
        'models' => [
            'setUp' => function() {
            },
            'tearDown' => function() {
            },
        ],
    ],
];

if (isset($tests['php'])) {
    foreach ($tests['php'] as $test) {
        doctest_run_php($test);
    }
}

if (isset($groups['php'])) {
    foreach ($groups['php'] as $groupId => $group) {
        doctest_run_php_group($group);
    }
}

if (isset($blocks['php'])) {
    foreach ($blocks['php'] as $block) {
        if ($errors = php_lint_errors($block['code'])) {
            $errstr = implode("\n - ", $errors);
            throw new \RuntimeException("Linting block on {$block['file']}:{$block['line']} failed with errors:\n - ".$errstr);
        }
    }
}

lint_nope_blocks: {
    $nope = new \Nope\Parser;

    foreach ($allBlocks as $block) {
        if ($block['lang'] == 'php') {
            $tokens = token_get_all($block['code']);
            foreach ($tokens as $token) {
                if (is_array($token) && $token[0] == T_DOC_COMMENT) {
                    $parsed = $nope->parseDocComment($token[1]);
                }
            }
        }
        elseif ($block['lang'] == 'nope') {
            $parsed = $nope->parseDocComment($block['code']);
        }
    }
}

function doctest_run_php_group($group)
{
    $scope = [];    
    foreach ($group as $test) {
        $scope = array_merge($scope, doctest_run_php($test, $scope));
    }
}

function doctest_run_php($test, $scope=[])
{
    global $config;
    global $testInfo;

    $id = isset($test['meta']['testid']) ? $test['meta']['testid'] : null;    
    $info = isset($testInfo['php'][$id]) ? $testInfo['php'][$id] : null;

    if (isset($info['setUp'])) {
        $scope = array_merge($scope, $info['setUp']() ?: []);
    }

    php_lint_errors($test['code'], !!'throw', $test['line']);

    $scope = doctest_php_eval($scope, $test['code']);
    return $scope;
}

function doctest_php_eval($scope)
{
    extract($scope);
    eval('?'.'>'.func_get_args()[1]);
    return get_defined_vars();
}

function doctest_find($docPath)
{
    $groups = [];
    $tests  = [];
    $blocks = [];
    $allBlocks= [];

    $id = 0;

    foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($docPath), \RecursiveIteratorIterator::LEAVES_ONLY) as $item) {
        $file = $item->getPathname();
        $name = $item->getFilename();
        if ($item->isDir() || $item->getExtension() != 'rst' || $name[0] == '.') {
            continue;
        }

        $h = fopen($file, 'r');
        $rstBlocks = rst_code_block_find($h);
        
        foreach ($rstBlocks as $block) {
            $block['file'] = $file;

            $claimed = false;

            if (isset($block['meta'])) {
                if ($test = array_intersect_key($block['meta'], ['test'=>true, 'testgroup'=>true, 'testseq'=>true])) {
                    $seq = isset($test['testseq']) ? $test['testseq'] : $id;
                    $group = isset($test['testgroup']) ? $test['testgroup'] : '';

                    if ($group) {
                        $groups[$block['lang']][$group]["$seq|".($id++)] = $block;
                    } else {
                        $tests[$block['lang']][] = $block;
                    }
                    $claimed = true;
                }


                if (isset($block['meta']['nolint']) && $block['meta']['nolint']) {
                    $claimed = true;
                }
            }

            if (!$claimed) {
                $blocks[$block['lang']][] = $block;
            }

            $allBlocks[] = $block;
        }
    }

    foreach ($groups as $lang=>$langGroups) {
        foreach ($langGroups as $groupId=>&$group) {
            ksort($group);
            $group = array_values($group);
        }
        unset($group);
    }

    return [$groups, $tests, $blocks, $allBlocks];
}


function iter_lines($h)
{
    // 100 wasn't enough
    $cacheLines = 1000;

    $cache = [];
    $i = 0;
    while (!feof($h) && $i++ < $cacheLines) {
        $line = fgets($h);
        if ($line === false) {
            break;
        }
        $cache[] = $line;
    }

    $indent = indent_detect($cache);

    return [$indent, function() use ($cache, $h) {
        foreach ($cache as $line) {
            yield $line;
        }

        while (!feof($h)) {
            $line = fgets($h);
            if ($line === false) {
                break;
            }
            yield $line;
        }
    }];
}

// The awfulness of the prototype. It works.
function rst_code_block_find($h)
{
    $ST_NONE = 0;
    $ST_META = 1;
    $ST_GAP = 2;
    $ST_CONTENT = 3;

    $state = 0;
    $current = [];
    $codeBlocks = [];

    $lastLevel = 0;
    
    list ($indent, $lines) = iter_lines($h);
    if (!$indent) {
        $indent = [!!'space', 3];
    }

    list ($isSpace, $indentSize) = $indent;

    $ip = function ($w) use ($isSpace, $indentSize) {
        if ($w) {
            return '(['.($isSpace ? ' ' : '\t').']{'.$indentSize.'}){'.$w.'}';
        } else {
            return '';
        }
    };

    foreach ($lines() as $idx=>$line) {
        $currentLevel = indent_count($line, $indent);
        $line = rtrim($line, "\n\r");

    parse_line:
        if ($state === $ST_NONE) {
            if (preg_match('/^ '.$ip($currentLevel).' \.\. \h* code-block \:\:\h* ( (?P<lang> \w+) \h*)? $/x', $line, $match)) {
                $current = [
                    'lang'   => $match['lang'],
                    'code'   => '',
                    'level'  => $currentLevel,
                    'line'   => $idx + 1,
                    'meta'   => [],
                ];
                $state = $ST_META;
                goto next_line;
            }
        }
        elseif ($state === $ST_META) {
            if ($currentLevel <= $current['level'] && preg_match('/^\h*[^\h]/', $line)) {
                $state = $ST_NONE;
                $current = [];
                goto next_line;
            }
            elseif (preg_match('/^ \h+ :(?P<key> \w+): \h* (?P<value> .*) $/x', $line, $match)) {
                $value = isset($match['value']) && trim($match['value']) ? trim($match['value']) : true;
                $current['meta'][$match['key']] = $value;
                goto next_line;
            }
            elseif (!trim($line)) {
                $state = $ST_CONTENT;
                goto next_line;
            }
            else {
                throw new \Exception();
            }
        }
        elseif ($state === $ST_CONTENT) {
            if ($currentLevel <= $current['level'] && preg_match('/^\h*[^\h]/', $line)) {
                $codeBlocks[] = $current;
                $current = [];
                $state = $ST_NONE;
                goto parse_line;
            }
            else {
                $dedent = preg_replace('/^'.$ip($current['level']+1).'/', '', $line);
                $current['code'] .= $dedent."\n";
            }
        }

    next_line:
        $lastLevel = $currentLevel;
    }

    if ($current) {
        $codeBlocks[] = $current;
    }

    return $codeBlocks;
}

// https://medium.com/firefox-developer-tools/detecting-code-indentation-eff3ed0fb56b
function indent_detect($lines)
{
    $counts = [];
    foreach ($lines as $line) {
        if (preg_match('/^([ \t]+)/', $line, $match)) {
            foreach (str_split($match[1]) as $char) {
                if (!isset($counts[$char])) {
                    $counts[$char] = 0;
                }
                $counts[$char]++;
            }
        }
    }
    if ($counts) {
        krsort($counts, SORT_NUMERIC);
        $isSpace = key($counts) == ' ';
    }
    else {
        $isSpace = null;
    }

    
    if ($isSpace) {
        $detectedSpaces = indent_detect_spaces($lines);
        if ($detectedSpaces) {
            return [true, $detectedSpaces];
        }
    }
    if ($isSpace === false) {
        return [false, 1];
    }
    return null;
}

function php_lint_errors($code, $throw=false, $line=null)
{
    $p = proc_open(PHP_BINARY.' -l', [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes);
    fwrite($pipes[0], $code);
    fclose($pipes[0]);
    $out = stream_get_contents($pipes[1]);
    $err = stream_get_contents($pipes[2]);
    $ret = proc_close($p);

    if ($ret !== 0) {
        $errors = preg_split('/(\r?\n)+/', trim($err), -1, PREG_SPLIT_NO_EMPTY);
    } else {
        $errors = [];
    }

    if ($errors && $throw) {
        $errstr = implode("\n - ", $errors);
        throw new \RuntimeException("Linting block failed with errors:\n - ".$errstr);
    }

    return $errors;
}

function indent_count($line, $indent)
{
    list ($isSpace, $indentSize) = $indent;
    $leading = indent_leading_spaces($line, $indent);
    return (int) floor($leading / $indentSize);
}

function indent_leading_spaces($line, $isSpace)
{
    $pattern = '/^(['.($isSpace ? ' ' : '\t').']+)/';
    if (preg_match($pattern, $line, $match)) {
        return strlen($match[1]);
    }
    return 0;
}

function indent_detect_spaces($lines)
{
    $indents = [];
    $last = 0;
    
    foreach ($lines as $line) {
        $width = indent_leading_spaces($line, true);
        $indent = abs($width - $last);
        if ($indent > 1) {
            $indents[$indent] = isset($indents[$indent]) ? $indents[$indent] + 1 : 1;
        }
        $last = $width;
    }

    $indent = null;
    $max = 0;
    foreach ($indents as $width=>$count) {
        if ($count > $max) {
            $max = $count;
            $indent = $width;
        }
    }
    return $indent;
}
