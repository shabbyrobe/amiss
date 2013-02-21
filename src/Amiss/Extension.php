<?php
namespace Amiss;

interface Extension
{
	function getTypeHandler($id);
	function getRelator($id);
}
