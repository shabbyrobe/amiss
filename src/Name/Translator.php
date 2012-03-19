<?php

namespace Amiss\Name;

/**
 * Interface for bi-directional name mapping
 */
interface Translator
{
	function to(array $names);
	function from(array $names); 
}
