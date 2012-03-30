<?php

namespace Amiss;

/**
 * Mapper interface
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
	 * Create the object
	 * 
	 * @param \Amiss\Meta $meta The metadata to use to create the object
	 * @param array $row The row values to use to populate the object. This is made
	 *   available to use to construct the object, not to populate it. Feel free to 
	 *   ignore it, and implement populateObject instead.
	 * @param array $args Class constructor arguments
	 * @return void
	 */
	function createObject($meta, $row, $args);
	
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
	 * Get row values from an object
	 * 
	 * @param \Amiss\Meta $meta
	 * @param object The object to get row values from
	 * @return array
	 */
	function exportRow($meta, $object);
	
	/**
	 * Get a type handler for a field type
	 * @param string $type The type of the field
	 * @return \Amiss\Type\Handler
	 */
	function determineTypeHandler($type);
}
