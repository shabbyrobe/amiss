<?php

namespace Amiss;

class Loader
{
	public function load($class)
	{
		if (strpos($class, 'Amiss\\')===0) {
			require(__DIR__.'/'.str_replace('\\', '/', str_replace('..', '', substr($class, 6))).'.php');
			return true;
		}
	}
}
