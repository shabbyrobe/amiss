<?php
namespace Amiss\Sql;

class RelatorContext
{
    private $stack = [];
    private $stackIdx = -1;
    private $metaCount = [];

    private $objects = [];

    public $meta;

    function add($objects)
    {
        if (!is_array($objects))
            $objects = [$objects];

        foreach ($objects as $object) {
            foreach ($this->meta->indexes as $indexName=>$index) {
                if ($index['key']) {
                    $value = $this->meta->getIndexValue($object, $indexName);
                    ksort($value);
                    $this->objects[$this->meta->class][$indexName][serialize($value)] = $object;
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
        if ($this->meta)
            $this->stack[++$this->stackIdx] = $this->meta;

        $this->meta = $meta;
    }

    function pop($meta)
    {
        $this->meta = $this->stack[--$this->stackIdx];
        if ($this->stackIdx < 0) {
            // Finalise... likely a gc cycle nightmare!
        }
    }
}
