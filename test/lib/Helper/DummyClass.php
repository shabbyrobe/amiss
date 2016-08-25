<?php
namespace Amiss\Test\Helper;

class DummyClass
{
    public function __get($name)
    {
        return isset($this->$name) ? $this->$name : null;
    }

    public function __set($name, $value)
    {
        $this->$name = $value;
    }

    public function __unset($name)
    {
        $this->$name = null;
    }

    public function __isset($name)
    {
        return isset($this->$name);
    }
}
