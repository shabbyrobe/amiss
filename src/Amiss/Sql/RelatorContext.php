<?php
namespace Amiss\Sql;

class RelatorContext
{
    private $stack = [];
    private $stackIdx = -1;
    private $metaCount = [];

    public $objects = [];

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
