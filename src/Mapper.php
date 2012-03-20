<?php

namespace Amiss;

// @codeCoverageIgnoreStart
interface Mapper
{
	function getMeta($class);
	function createObject($meta, $row, $args);
	function populateObject($meta, $object, $args);
	function exportRow($meta, $object);
	function determineTypeHandler($type);
}
