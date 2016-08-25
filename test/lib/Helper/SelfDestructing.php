<?php
namespace Amiss\Test\Helper;

class SelfDestructing
{
    private $__destruct__;

    public function __construct($data, callable $destruct)
    {
        foreach ($data as $k=>$v) {
            $this->$k = $v;
        }
        $this->__destruct__ = $destruct;
    }

    public function destroy()
    {
        $cb = $this->__destruct__;
        $cb($this);
        foreach (get_object_vars($this) as $k=>$v) {
            unset($this->$k);
        }
    }

    public function __destruct()
    {
        if ($this->__destruct__) {
            $cb = $this->__destruct__;
            $cb($this);
        }
    }
}
