<?php
namespace Amiss;

/**
 * Mapper interface
 * 
 * The Mapper interface provides three methods that may appear to be very
 * similar, but are necessarily distinct and separate:
 * 
 *   - mapRowToObject
 *   - createObject
 *   - populateObject 
 * 
 * Basically, if you want:
 * 
 *   - A fully constructed and populated object based on input: use ``mapRowToObject``
 *   - An instance of an object from the mapper that is not yet fully populated
 *     from input: use ``createObject``
 *   - An instance you already have lying around to be populated by the mapper:
 *     use ``populateObject``.
 */
abstract class Mapper
{
    /**
     * Get the metadata for the class
     * @param mixed  String class name or object
     * @return \Amiss\Meta
     */
    public abstract function getMeta($class);

    public abstract function mapRowToProperties($input, $meta=null, $fieldMap=null);

    public abstract function mapPropertiesToRow($input, $meta=null);

    /**
     * Get row values from an object
     * 
     * @param $meta Amiss\Meta or string used to call getMeta()
     * @param $input object The object to get row values from
     * @param $context Identifies the context in which the export is occurring. Useful
     *     for distinguishing between inserts and updates when dealing with sql databases.
     * 
     * @return array
     */
    public abstract function mapObjectToRow($input, $meta=null, $context=null);

    /**
     * Get a type handler for a field type
     * @param string $type The type of the field
     * @return \Amiss\Type\Handler
     */
    public abstract function determineTypeHandler($type);

    /**
     * Create and populate an object
     * @param $meta Amiss\Meta or string used to call getMeta()
     */
    public function mapRowToObject($input, $args=null, $meta=null)
    {
        if (!$meta instanceof Meta) {
            $meta = $this->getMeta($meta ?: $input);
        }

        $mapped = $this->mapRowToProperties($input, $meta);
        $object = $this->createObject($meta, $mapped, $args);
        $this->populateObject($object, $mapped, $meta);

        return $object;
    }

    public function mapObjectsToProperties($objects, $meta=null)
    {
        $output = [];
        foreach ($objects as $idx=>$object) {
            if (!$meta instanceof Meta) {
                $meta = $this->getMeta($meta ?: $object);
            }
            $output[$idx] = $this->mapObjectToProperties($object, $meta);
        }
        return $output;
    }

    public function mapObjectToProperties($object, $meta=null)
    {
        if (!$meta instanceof Meta) {
            $meta = $this->getMeta($meta ?: $object);
        }

        $output = [];
        foreach ($meta->getFields() as $prop=>$field) {
            if (!isset($field['getter'])) {
                $value = $object->$prop;
            } else {
                $value = call_user_func(array($object, $field['getter']));
            }
            $output[$field['id']] = $value;    
        }
        return $output;
    }

    public function formatParams(Meta $meta, $propertyParamMap, $params)
    {
        return $params;
    }

    /**
     * @param $meta Amiss\Meta or string used to call getMeta()
     */
    public function mapRowsToObjects($input, $args=null, $meta=null)
    {
        if (!$input) {
            return [];
        }
        if (!$meta instanceof Meta) {
            $meta = $this->getMeta($meta ?: current($input));
            if (!$meta) { throw new \InvalidArgumentException(); }
        }
        $out = array();
        if ($input) {
            foreach ($input as $item) {
                $obj = $this->mapRowToObject($item, $args, $meta);
                $out[] = $obj;
            }
        }
        return $out;
    }

    /**
     * Get row values from a list of objects
     * 
     * This will almost always have the exact same body. This is provided for
     * convenience, commented out below the definition.
     *
     * @param $meta Amiss\Meta or string used to call getMeta()
     */
    public function mapObjectsToRows($input, $meta=null, $context=null)
    {
        if (!$input) { return []; }

        if (!$meta instanceof Meta) {
            $meta = $this->getMeta($meta ?: current($input));
        }

        $out = [];
        foreach ($input as $key=>$item) {
            $out[$key] = $this->mapObjectToRow($item, $meta, $context);
        }
        return $out;
    }
    
    /**
     * The row is made available to this function, but this is so it can be
     * used to construct the object, not to populate it. Feel free to ignore it, 
     * it will be passed to populateObject as well.
     * 
     * @param $meta \Amiss\Meta  The metadata to use to create the object
     * @param array $mapped      Input values after mapping to property names and type handling
     * @param array $args        Class constructor arguments
     * @return void
     */
    public function createObject($meta, $mapped, $args=null)
    {
        if (!$meta instanceof Meta) {
            $meta = $this->getMeta($meta);
        }

        $object = null;
        $args = $args ? array_values($args) : [];
        $argc = $args ? count($args) : 0;

        if ($meta->constructorArgs) {
            $actualArgs = [];
            foreach ($meta->constructorArgs as list($type, $id)) {
                switch ($type) {
                case 'property':
                    $actualArgs[] = isset($mapped->{$id}) ? $mapped->{$id} : null;
                    unset($mapped->{$id});
                break;

                case 'arg':
                    if ($id >= $argc) {
                        throw new Exception("Class {$meta->class} requires argument at index $id - please use Select->\$args");
                    }
                    $actualArgs[] = $args[$id];
                break;

                default:
                    throw new Exception("Invalid constructor argument type {$type}");
                }
            }
            $args = $actualArgs;
        }

        if ($meta->constructor == '__construct') {
            if ($args) {
                $rc = new \ReflectionClass($meta->class);
                $object = $rc->newInstanceArgs($args);
            }
            else {
                $cname = $meta->class;
                $object = new $cname;
            }
        }
        else {
            $rc = new \ReflectionClass($meta->class);
            $method = $rc->getMethod($meta->constructor);
            $object = $method->invokeArgs(null, $args ?: []);
        }

        if (!$object instanceof $meta->class) {
            throw new Exception(
                "Constructor {$meta->constructor} did not return instance of {$meta->class}"
            );
        }

        return $object;
    }
    
    /**
     * Populate an object with row values
     * 
     * @param $meta  Amiss\Meta|string
     * @param object $object            
     * @param array  $mapped Input after mapping to property names and type handling
     * @return void
     */
    public function populateObject($object, \stdClass $mapped, $meta=null)
    {
        if (!$meta instanceof Meta) {
            $meta = $this->getMeta($meta ?: $object);
            if (!$meta) {
                throw new \InvalidArgumentException();
            }
        }

        $properties = $meta->getProperties();
        foreach ($mapped as $prop=>$value) {
            if (!isset($properties[$prop])) {
                throw new \UnexpectedValueException("Property $prop does not exist on class {$meta->class}");
            }
            $property = $properties[$prop];
            if (!isset($property['setter'])) {
                $object->{$prop} = $value;
            }
            else {
                // false setter means read only
                if ($property['setter'] !== false) {
                    call_user_func(array($object, $property['setter']), $value);
                }
            }
        }
    }    
}
