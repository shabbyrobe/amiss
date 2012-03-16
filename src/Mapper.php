<?php 

namespace Amiss;

interface Mapper
{
	function getMeta($class);
	function resolveObjectName($name);
	function createObject($meta, $row, $args);
	function exportRow($meta, $object);
	function determineTypeHandler($type);
}
