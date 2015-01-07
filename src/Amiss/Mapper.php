<?php
namespace Amiss;

/**
 * Mapper interface
 * 
 * The Mapper interface provides three methods that may appear to be very
 * similar, but are necessarily distinct and separate:
 * 
 *   - toObject
 *   - createObject
 *   - populateObject 
 * 
 * Basically, if you want:
 * 
 *   - A fully constructed and populated object based on input: use ``toObject``
 *   - An instance of an object from the mapper that is not yet fully populated
 *     from input: use ``createObject``
 *   - An instance you already have lying around to be populated by the mapper:
 *     use ``populateObject``.
 */
abstract class Mapper
{
    /**
     * Get the metadata for the class
     * @param string Class name
     * @return \Amiss\Meta
     */
    public abstract function getMeta($class);

    /**
     * Create and populate an object
     * @param $meta Amiss\Meta or string used to call getMeta()
     */
    public function toObject($meta, $input, $args=null)
    {
        if (!$meta instanceof Meta) { $meta = $this->getMeta($meta); }

        $mapped = $this->mapValues($meta, $input);
        $object = $this->createObject($meta, $mapped, $args);
        $this->populateObject($meta, $object, $mapped);

        return $object;
    }

    /**
     * @param $meta Amiss\Meta or string used to call getMeta()
     */
    public function toObjects($meta, $input, $args=null)
    {
        if (!$meta instanceof Meta) { $meta = $this->getMeta($meta); }

        $out = array();
        if ($input) {
            foreach ($input as $item) {
                $obj = $this->toObject($meta, $item);
                $out[] = $obj;
            }
        }
        return $out;
    }

    public function mapValues($meta, $input, $fieldMap=null)
    {
        if (!$fieldMap) { $fieldMap = $meta->getColumnToPropertyMap(); }

        $mapped = [];
        $fields = $meta->getFields();
        $defaultType = null;

        foreach ($input as $col=>$value) {
            if (!isset($fieldMap[$col])) {
                continue;
                // throw new \UnexpectedValueException("Unexpected key $col in input");
            }

            $prop = $fieldMap[$col];
            $field = $fields[$prop];
            $type = $field['type'];
            if (!$type) {
                if ($defaultType === null) {
                    $defaultType = $meta->getDefaultFieldType() ?: false;
                }
                $type = $defaultType;
            }
            
            if ($type) {
                $typeId = $type['id'];
                if (!isset($this->typeHandlerMap[$typeId])) {
                    $this->typeHandlerMap[$typeId] = $this->determineTypeHandler($typeId);
                }
                if ($this->typeHandlerMap[$typeId]) {
                    $value = $this->typeHandlerMap[$typeId]->handleValueFromDb($value, $field, $input);
                }
            }

            if (isset($mapped[$prop])) {
                throw new \UnexpectedValueException();
            }
            $mapped[$prop] = $value;
        }

        return $mapped;
    }

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
    public abstract function fromObject($meta, $input, $context=null);

    /**
     * Get row values from a list of objects
     * 
     * This will almost always have the exact same body. This is provided for
     * convenience, commented out below the definition.
     *
     * @param $meta Amiss\Meta or string used to call getMeta()
     */
    public function fromObjects($meta, $input, $context=null)
    {
        if (!$meta instanceof Meta) { $meta = $this->getMeta($meta); }

        $out = array();
        if ($input) {
            foreach ($input as $key=>$item) {
                $out[$key] = $this->fromObject($meta, $item, $context);
            }
        }
        return $out;
    }
    
    /**
     * The row is made available to this function, but this is so it can be
     * used to construct the object, not to populate it. Feel free to ignore it, 
     * it will be passed to populateObject as well.
     * 
     * @param $meta \Amiss\Meta The metadata to use to create the object
     * @param array $row The row values, which can be used to construct the object.
     * @param array $args Class constructor arguments
     * @return void
     */
    public abstract function createObject($meta, $row, $args=null);
    
    /**
     * Populate an object with row values
     * 
     * @param $meta Amiss\Meta or string used to call getMeta()
     * @param object $object The object to populate
     * @param array $row The row values to use to populate the object
     * @return void
     */
    public function populateObject($meta, $object, array $mapped)
    {
        if (!$meta instanceof Meta) { $meta = $this->getMeta($meta); }

        $fields = $meta->getFields();
        foreach ($mapped as $prop=>$value) {
            $field = $fields[$prop];
            if (!isset($field['setter'])) {
                $object->{$prop} = $value;
            }
            else {
                // false setter means read only
                if ($field['setter'] !== false) {
                    call_user_func(array($object, $field['setter']), $value);
                }
            }
        }
    }
    
    /**
     * Get a type handler for a field type
     * @param string $type The type of the field
     * @return \Amiss\Type\Handler
     */
    public abstract function determineTypeHandler($type);
}
