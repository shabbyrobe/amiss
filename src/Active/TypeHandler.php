<?php

namespace Amiss\Active;

interface TypeHandler
{
	function prepareValueForDb($value);
	
	function handleValueFromDb($value);
	
	/**
	 * It's ok to return nothing from this - the default column type
	 * will be used.
	 */
	function createColumnType($engine);
}
