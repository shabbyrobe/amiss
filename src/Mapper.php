<?php

namespace Amiss;

abstract class Mapper
{
	abstract function resolveObjectName($name);
	abstract function getMeta($class);
	abstract function getRowValues($object);
}
