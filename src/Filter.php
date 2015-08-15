<?php
namespace Amiss;

class Filter
{
    private $mapper;

    function __construct(\Amiss\Mapper $mapper)
    {
        $this->mapper = $mapper;
    }

    /**
     * Retrieve all object child values through a property path.
     *
     * Path can be an array:      ['prop', 'childProp', 'veryChildProp']
     * Or a '/' delimited string: prop/childProp/veryChildProp
     * 
     * @param object[] $objects
     * @param string|array $path
     * @return array
     */
    public function getChildren($objects, $path)
    {
        $array = array();
        if (!is_array($path)) {
            $path = explode('/', $path);
        }
        if (!is_array($objects)) {
            $objects = array($objects);
        }

        $ret = $objects;

        foreach ($path as $child) { 
            $array = [];
            $meta = null;
            $properties = null;
            
            if ($ret && is_object(current($ret))) {
                $meta = $this->mapper->getMeta(get_class(current($ret)), !'strict');
                if ($meta) {
                    $properties = $meta->getProperties();
                }
            }

            foreach ($ret as $o) {
                $field = isset($properties[$child]) ? $properties[$child] : null;
                if (!$field || !isset($field['getter'])) {
                    $value = $o->{$child};
                } else {
                    $g = [$o, $field['getter']];
                    $value = $g();
                }
                
                if (is_array($value) || $value instanceof \Traversable) {
                    $array = array_merge($array, $value);
                }
                elseif ($value !== null) {
                    $array[] = $value;
                }
            }

            $ret = $array;
        }

        return $ret;
    }

    /**
     * Iterate over an array of objects and returns an array of objects
     * indexed by a property
     *
     * @return array
     */
    public function indexBy($list, $property, $meta=null, $allowDupes=null, $ignoreNulls=null)
    {
        $allowDupes  = $allowDupes  !== null ? $allowDupes  : false;
        $ignoreNulls = $ignoreNulls !== null ? $ignoreNulls : true;

        if (!$list) {
            return [];
        }

        if ($meta) {
            $meta = !$meta instanceof Meta ? $this->mapper->getMeta($meta) : $meta;
        }
        else {
            if (!($first = current($list))) {
                throw new \UnexpectedValueException();
            }
            $meta = $this->mapper->getMeta(get_class($first));
        }

        $index = array();

        $props = $meta ? $meta->getProperties() : [];
        foreach ($list as $object) {
            $propDef = !isset($props[$property]) ? null : $props[$property];
            $value = !$propDef || !isset($propDef['getter']) 
                ? $object->{$property} 
                : call_user_func(array($object, $propDef['getter']));

            if ($value === null && $ignoreNulls) {
                continue;
            }
            if (!$allowDupes && isset($index[$value])) {
                throw new \UnexpectedValueException("Duplicate value for property $property");
            }
            $index[$value] = $object;
        }
        return $index;
    }

    /**
     * Iterate over an array of objects and group them by the value of a property
     *
     * @return array[group] = class[]
     */
    public function groupBy($list, $property, $meta=null)
    {
        if (!$list) {
            return [];
        }
        if (!$meta) {
            if (!($first = current($list))) {
                throw new \UnexpectedValueException();
            }
            $meta = $this->mapper->getMeta(get_class($first));
        }

        $groups = [];

        $props = $meta->getProperties();
        foreach ($list as $object) {
            $propDef = !isset($props[$property]) ? null : $props[$property];
            $value = !$propDef || !isset($propDef['getter']) 
                ? $object->{$property} 
                : call_user_func(array($object, $propDef['getter']));

            $groups[$value][] = $object;
        }
        return $groups;
    }
}
