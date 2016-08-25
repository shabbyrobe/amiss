<?php
namespace Amiss\Sql\Relator;

use Amiss\Meta;
use Amiss\Sql\Query\Criteria;

/**
 * Performs a lookup against a class with a static method.
 *
 * Example:
 *
 *     class Thing {
 *         private static $data = [
 *             1 => ['foo' => 'bar'],
 *             2 => ['foo' => 'baz'],
 *         ];
 *         function __construct($in)       { $this->foo = $in['foo']; }
 *         static function gimmeThing($id) { return new static(static::$data[$id]); }
 *     }
 *    
 *     class Model {
 *         /** :amiss = {"field": true}; *../
 *         public $thingId;
 *    
 *         /** 
 *          * :amiss = {
 *          *     "has": "static", 
 *          *     "of": "Thing", 
 *          *     "call": "gimmeThing", 
 *          *     "argFields": ["thingId"], 
 *          *     "mode": "auto"
 *          * };
 *          *../
 *         public $thing;
 *     }
 */
class StaticLookup implements \Amiss\Sql\Relator
{
    public $staticConstructor = 'get';

    function getRelated(Meta $meta, $source, array $relation, Criteria $criteria=null)
    {
        if (!$source) {
            return;
        }
        $result = $this->fetchRelated($meta, [$source], $relation, $criteria);
        return current($result);
    }

    function getRelatedForList(Meta $meta, $source, array $relation, Criteria $criteria=null)
    {
        if (!$source) {
            return;
        }
        return $this->fetchRelated($meta, $source, $relation, $criteria);
    }

    private function fetchRelated(Meta $meta, $source, array $relation, Criteria $criteria=null)
    {
        if (!isset($relation['of'])) {
            throw new \UnexpectedValueException("Class {$meta->class}, relation $relationName - no 'of'");
        }

        $class = $relation['of'];
        $argFields = isset($relation['argfields']) ? (array) $relation['argfields'] : [];
        $method = isset($relation['call']) ? $relation['call'] : $this->staticConstructor;

        $argFieldsMeta = [];
        $fields = $meta->fields;
        foreach ($argFields as $argField) {
            $argFieldsMeta[$argField] = $fields[$argField];
        }

        $result = [];
        foreach ($source as $s) {
            $args = [];
            foreach ($argFieldsMeta as $prop=>$field) {
                $arg = $s instanceof \stdClass || !isset($field['getter']) 
                    ? $s->{$prop} 
                    : call_user_func(array($s, $field['getter'])
                );
                if ($arg) {
                    $args[] = $arg;
                }
            }
            if ($args) {
                $result[] = call_user_func_array([$class, $method], $args);
            }
        }
        return $result;
    }
}
