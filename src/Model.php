<?php
namespace Amiss;

abstract class Model
{
    private static $meta = [];

    protected static function meta()
    {
        throw new \Exception("Must override in your own class");
    }

    static function getMeta()
    {
        $class = get_called_class();
        if (isset(self::$meta[$class])) {
            return self::$meta[$class];
        }

        $meta = new Meta($class, static::meta());

        $props = array_change_key_case($meta->getProperties());
        $rc = new \ReflectionClass($class);
        foreach ($rc->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $lower = strtolower($method->name);
            $is = null;
            $propId = null;

            if ($lower[0] == 'g' && $lower[1] == 'e' && $lower[2] == 't') {
                // getFoo -> foo
                $propId = substr($lower, 3);
                $is = 'getter';
            }
            elseif ($lower[0] == 's' && $lower[1] == 'e' && $lower[2] == 't') {
                // setFoo -> foo
                $propId = substr($lower, 3);
                $is = 'setter';
            }
            elseif ($lower[0] == 'i' && $lower[1] == 's') {
                // isFoo -> foo
                $propId = substr($lower, 2);
                $is = 'getter';
            }
            elseif ($lower[0] == 'h' && $lower[1] == 'a' && $lower[2] == 's') {
                // hasFoo -> foo
                $propId = substr($lower, 3);
                $is = 'getter';
            }
            
            if ($propId && isset($props[$propId])) {
                $prop = $props[$propId];
                $source = $prop['source'];

                if ($is === 'getter') {
                    $meta->{$source}[$prop['id']]['getter'] = $method->name;
                } elseif ($is === 'setter') {
                    $meta->{$source}[$prop['id']]['setter'] = $method->name;
                }
            }
        }

        return self::$meta[$class] = $meta;
    }

    function __get($name)
    {
        $meta = static::getMeta();
        $properties = $meta->getProperties();

        if (isset($properties[$name])) {
            $property = $properties[$name];
            if (!isset($property['getter'])) {
                return isset($this->$name) ? $this->$name : null;
            }
            else {
                $m = $property['getter'];
                return $this->$m();
            }
        }
        throw new \BadMethodCallException("Unknown property $name");
    }

    function __set($name, $value)
    {
        $meta = static::getMeta();
        $properties = $meta->getProperties();

        if (isset($properties[$name])) {
            $property = $properties[$name];
            if (!isset($property['setter'])) {
                $this->$name = $value;
                return;
            }
            else {
                if ($property['setter'] !== false) {
                    $m = $property['setter'];
                    $this->$m($value);
                    return;
                } else {
                    throw new \BadMethodCallException("Read only property $name");
                }
            }
        }
        throw new \BadMethodCallException("Unknown property $name");
    }

    function __isset($name)
    {
        $meta = static::getMeta();
        $properties = $meta->getProperties();
        if (!isset($properties[$name])) {
            throw new \BadMethodCallException("Unknown property $name");
        }
        return isset($this->$name);
    }
    
    function __unset($name)
    {
        $this->$name = null;
    }
}
