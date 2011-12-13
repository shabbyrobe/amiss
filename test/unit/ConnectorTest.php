<?php

namespace Amiss\Test\Unit;

use Amiss\Connector;

class ConnectorTest extends \CustomTestCase
{
	public function testEngine()
	{
		$c = new Connector('pants:foo=bar');
		$this->assertEquals('pants', $c->engine);
	}
}
