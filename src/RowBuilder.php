<?php

namespace Amiss;

interface RowBuilder
{
	/**
	 * @return object The object created from the row
	 */
	function buildObject(array $row);
}
