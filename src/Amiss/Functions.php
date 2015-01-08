<?php
namespace Amiss;

class Functions
{
    const INDEX_DUPE_CONTINUE = 0;
    
    const INDEX_DUPE_FAIL = 1;
    
    /**
     * Iterate over an array of objects and returns an array of objects
     * indexed by a property
     * 
     * @param array The list of objects to index
     * @param string The property to index by
     * @param integer Index mode
     * @return array
     */
    public static function indexBy($list, $property, $mode=self::INDEX_DUPE_CONTINUE)
    {
        $index = array();
        foreach ($list as $i) {
            if ($mode === self::INDEX_DUPE_FAIL && isset($index[$i->$property])) {
                throw new \UnexpectedValueException("Duplicate value for property $property");
            }
            $index[$i->$property] = $i;
        }
        return $index;
    }
    
    /**
     * Create a one-dimensional associative array from a list of objects, or a list of 2-tuples.
     * 
     * @param object[]|array $list
     * @param string $keyProperty
     * @param string $valueProperty
     * @return array
     */
    public static function keyValue($list, $keyProperty=null, $valueProperty=null)
    {
        $index = array();
        foreach ($list as $i) {
            if ($keyProperty) {
                if (!$valueProperty) { 
                    throw new \InvalidArgumentException("Must set value property if setting key property");
                }
                $index[$i->$keyProperty] = $i->$valueProperty;
            }
            else {
                $key = current($i);
                next($i);
                $value = current($i);
                $index[$key] = $value;
            }
        }
        return $index;
    }
    
    /**
     * Retrieve all object child values through a property path.
     * 
     * @param object[] $objects
     * @param string|array $path
     * @return array
     */
    public static function getChildren($objects, $path)
    {
        $array = array();
        if (!is_array($path)) {
            $path = explode('/', $path);
        }
        if (!is_array($objects)) {
            $objects = array($objects);
        }
        
        $count = count($path);
        
        foreach ($objects as $o) {
            $value = $o->{$path[0]};
            
            if (is_array($value) || $value instanceof \Traversable) {
                $array = array_merge($array, $value);
            }
            elseif ($value !== null) {
                $array[] = $value;
            }
        }
        
        if ($count > 1) {
            $array = self::getChildren($array, array_slice($path, 1));
        }
        
        return $array;
    }
}
