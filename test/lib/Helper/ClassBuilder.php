<?php
namespace Amiss\Test\Helper;

class ClassBuilder
{
    private static $classes = [];

    public static function i($reset=false)
    {
        static $i;
        if ($reset || $i===null) {
            $i = new static;
        }
        return $i;
    }

    public function register($classes)
    {
        $classes = (array)$classes;

        $hash = '';
        foreach ($classes as $k=>$v) {
            $hash .= "$k|$v|";
        }
        $classHash = hash('sha256', $hash);
        if (isset(self::$classes[$classHash])) {
            list ($ns, $classes) = self::$classes[$classHash];
        }
        else {
            $ns = "__AmissTest_".$classHash;
            $script = "namespace $ns;";
            foreach ($classes as $k=>$v) {
                $script .= $v;
            }
            $classes = get_declared_classes();
            eval($script);
            $classes = array_values(array_diff(get_declared_classes(), $classes));
            self::$classes[$classHash] = [$ns, $classes];
        }
        return [$ns, $classes];
    }

    public function registerOne($class)
    {
        list ($ns, $classes) = $this->register($class);
        if (($cnt = count($classes)) != 1) {
            throw new \UnexpectedValueException("Expected one class, found $cnt. Warning! Any classes have been registered in spite of this exception!");
        }
        return current($classes);
    }
}
