<?php

namespace Amiss;

interface TypeHandler
{
	function prepareValueForDb($value, $object, $fieldName);
	
	function handleValueFromDb($value, $object, $fieldName);
	
	/**
	 * It's ok to return nothing from this - the default column type
	 * will be used.
	 */
	function createColumnType($engine);
}
