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
     * @param  $id      string  ID of entity to map (probably class name, but not necessarily)
     * @param  $strict  bool    Throws an exception if the class isn't mapped.
     * @return \Amiss\Meta
     */
    function getMeta($id, $strict=true);

    function canMap($id);

    function mapRowToProperties($meta, $row, $fieldMap=null);

    function mapPropertiesToRow($meta, $properties);

    /**
     * Get row values from an object
     * 
     * @param $input   object  The object to get row values from
     * @param $meta Amiss\Meta or string used to call getMeta()
     * @param $context mixed   Identifies the context in which the export is occurring.
     *                         Useful for distinguishing between inserts and updates when
     *                         dealing with sql databases.
     * 
     * @return array
     */

    function mapObjectsToRows($object, $meta=null, $context=null);

    /**
     * Create and populate an object
     * @param $meta Amiss\Meta or string used to call getMeta()
     */
    function mapRowToObject($meta, $row, $args=null);

    function mapRowsToObjects($meta, $rows, $args=null);

    function mapObjectsToProperties($objects, $meta=null);

    function mapObjectToProperties($object, $meta=null);

    function formatParams(Meta $meta, $propertyParamMap, $params);

    function createObject($meta, $mapped, $args=null);

    function populateObject($object, \stdClass $mapped, $meta=null);

    /**
     * Get a type handler for a field type
     * @param  string  $type  The type of the field
     * @return \Amiss\Type\Handler
     */
    function determineTypeHandler($type);
}
