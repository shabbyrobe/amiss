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
interface Mapper
{
    const AUTOINC_TYPE = 'autoinc';

    /**
     * @param  $class   mixed  String class name or object
     * @param  $strict  bool   Throws an exception if the class isn't mapped.
     * @return \Amiss\Meta
     */
    function getMeta($class, $strict=true);

    function mapsClass($class);

    function mapRowToProperties($input, $meta=null, $fieldMap=null);

    function mapPropertiesToRow($input, $meta=null);

    /**
     * Get row values from an object
     * 
     * @param $meta Amiss\Meta or string used to call getMeta()
     * @param $input   object  The object to get row values from
     * @param $context mixed   Identifies the context in which the export is occurring.
     *                         Useful for distinguishing between inserts and updates when
     *                         dealing with sql databases.
     * 
     * @return array
     */
    function mapObjectToRow($input, $meta=null, $context=null);

    /**
     * Get a type handler for a field type
     * @param  string  $type  The type of the field
     * @return \Amiss\Type\Handler
     */
    function determineTypeHandler($type);

    /**
     * Create and populate an object
     * @param $meta Amiss\Meta or string used to call getMeta()
     */
    function mapRowToObject($input, $args=null, $meta=null);

    function mapObjectsToProperties($objects, $meta=null);

    function mapObjectToProperties($object, $meta=null);

    function formatParams(Meta $meta, $propertyParamMap, $params);

    function mapRowsToObjects($input, $args=null, $meta=null);

    function mapObjectsToRows($input, $meta=null, $context=null);

    function createObject($meta, $mapped, $args=null);

    function populateObject($object, \stdClass $mapped, $meta=null);
}
