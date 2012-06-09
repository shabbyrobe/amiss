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
 * 
 * @codeCoverageIgnoreStart
 */
interface Mapper
{
    /**
     * Get the metadata for the class
     * @param string Class name
     * @return \Amiss\Meta
     */
    function getMeta($class);
    
    /**
     * Create and populate an object
     * 
     * This will almost always have the exact same body. This is provided for
     * convenience, commented out below the definition.
     */
    function toObject($meta, $input, $args=null);
    //{
    //    $object = $this->createObject($meta, $row, $args);
    //    $this->populateObject($meta, $object, $row);
    //    return $object;
    //}
    
    /**
     * Get row values from an object
     * 
     * @param \Amiss\Meta $meta
     * @param object The object to get row values from
     * @return array
     */
    function fromObject($meta, $object);
    
    /**
     * Create the object
     * 
     * The row is made available to this function, but this is so it can be
     * used to construct the object, not to populate it. Feel free to ignore it, 
     * it will be passed to populateObject as well.
     * 
     * @param \Amiss\Meta $meta The metadata to use to create the object
     * @param array $row The row values, which can be used to construct the object.
     * @param array $args Class constructor arguments
     * @return void
     */
    function createObject($meta, $row, $args=null);
    
    /**
     * Populate an object with row values
     * 
     * @param \Amiss\Meta $meta 
     * @param object $object The object to populate
     * @param array $row The row values to use to populate the object
     * @return void
     */
    function populateObject($meta, $object, $row);
    
    /**
     * Get a type handler for a field type
     * @param string $type The type of the field
     * @return \Amiss\Type\Handler
     */
    function determineTypeHandler($type);
}
