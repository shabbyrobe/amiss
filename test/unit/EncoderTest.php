<?php

namespace Amiss\Test\Unit;

use Amiss\Type;

class EncoderTest extends \CustomTestCase
{
	public function setUp()
	{}

	/**
	 * @dataProvider dataForEncodeDecode
	 */
	public function testEncodeDecode($enc, $raw, $serialised)
	{
		$obj = new \stdClass;

		$result = $enc->prepareValueForDb($raw, $obj, array());
		$this->assertEquals($result, $serialised);

		$result = $enc->handleValueFromDb($serialised, $obj, array(), array());
		$this->assertEquals($result, $raw);
	}

	public function dataForEncodeDecode()
	{
		$tests[] = $this->dataForJsonEncode();
		$tests[] = $this->dataForClosureEncode();
		$tests[] = $this->dataForSerialise();

		$return = array();
		foreach ($tests as $test) {
			foreach ($test[1] as $t) {
				array_unshift($t, $test[0]);
				$return[] = $t;
			}
		}

		return $return;
	}

	public function dataForSerialise()
	{
		return array(new Type\Encoder('serialize', 'unserialize'), array(
			array('1', 's:1:"1";'),
			array('foo', 's:3:"foo";'),
			array(array(1, 2, 3), 'a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}'),
			array(array('a', 'b', array('c', 'd')), 'a:3:{i:0;s:1:"a";i:1;s:1:"b";i:2;a:2:{i:0;s:1:"c";i:1;s:1:"d";}}'),
		));
	}

	public function dataForJsonEncode()
	{
		return array(new Type\Encoder('json_encode', 'json_decode'), array(
			array('1', '"1"'),
			array('foo', '"foo"'),
			array(array(1, 2, 3), '[1,2,3]'),
			array(array('a', 'b', array('c', 'd')), '["a","b",["c","d"]]'),
		));
	}

	public function dataForClosureEncode()
	{
		$tests = $this->dataForJsonEncode();
		$tests[0] = new Type\Encoder(
			function($value) { return json_encode($value); },
			function($value) { return json_decode($value); }
		);
		return $tests;
	}
}
