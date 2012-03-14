<?php

namespace Amiss;

class Loader
{
	public function load($class)
	{
		if (strpos($class, 'Amiss\\')===0) {
			$file = __DIR__.'/'.str_replace('\\', '/', str_replace('..', '', substr($class, 6))).'.php';
			if (!file_exists($file))
				throw new \Exception($file);
			require($file);
			return true;
		}
	}
}
