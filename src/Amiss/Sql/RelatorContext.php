<?php
namespace Amiss\Sql;

class RelatorContext
{
    private $stack = [];
    private $stackIdx = -1;
    private $metaCount = [];

    private $objects = [];

    function add($objects)
    {
        if (!is_array($objects))
            $objects = [$objects];

        $meta = $this->stack[$this->stackIdx];
        foreach ($objects as $object) {
            foreach ($meta->indexes as $indexName=>$index) {
                if ($index['key']) {
                    $value = $meta->getIndexValue($object, $indexName);
                    ksort($value);
                    $this->objects[$meta->class][$indexName][serialize($value)] = $object;
                }
            }
        }
    }

    function get($class, $object, $indexName, array $indexValue)
    {
        ksort($indexValue);
        return $this->objects[$class][$object][$indexName][serialize($indexValue)];
    }

    function push($meta)
    {
        $this->stack[++$this->stackIdx] = $meta;
        $c = &$this->metaCount[$meta->class];
        $c = $c ? $c + 1 : 1;
    }

    function pop($meta)
    {
        --$this->stackIdx;
        --$this->metaCount[$meta->class];
        if ($this->stackIdx < 0) {
            // Finalise... likely a gc cycle nightmare!
        }
    }
}
