<?php

namespace Amiss\Tests\Unit;

class NoteParserTest extends \CustomTestCase
{
	public function setUp()
	{
		$this->parser = new \Amiss\Note\Parser;
	}
	
	/**
	 * @group unit
	 * @covers Amiss\Note\Parser::parseClass
	 * @covers Amiss\Note\Parser::parseReflectors
	 */
	public function testParseFullClass()
	{
		$info = $this->parser->parseClass(new \ReflectionClass(__NAMESPACE__.'\ParserTestClass'));
		$expected = (object)array(
			'notes'=>array('classNote'=>true),
			'methods'=>array(
				'method'=>array('methodNote'=>true),
				'casedMethod'=>array('methodNote'=>true),
			),
			'properties'=>array(
				'property'=>array('propertyNote'=>true),
			),
		);
		
		$this->assertEquals($expected, $info);
	}
	
	/**
	 * @group unit
	 * @covers Amiss\Note\Parser::parseComment
	 */
	public function testParseCondensedValuelessNote()
	{
		$parsed = $this->parser->parseComment('/** @ab */');
		$this->assertEquals(array('ab'=>true), $parsed);
	}
	
	/**
	 * @group unit
	 * @covers Amiss\Note\Parser::parseComment
	 */
	public function testParseCondensedValueNote()
	{
		$parsed = $this->parser->parseComment('/** @ab yep */');
		$this->assertEquals(array('ab'=>'yep'), $parsed);
	}
	
	/**
	 * @group unit
	 * @covers Amiss\Note\Parser::parseComment
	 */
	public function testParseSingleValuelessNote()
	{
		$parsed = $this->parser->parseComment(
			'/**'.PHP_EOL.' * @ab'.PHP_EOL.' */'
		);
		$this->assertEquals(array('ab'=>true), $parsed);
	}

	/**
	 * @group unit
	 * @covers Amiss\Note\Parser::parseComment
	 */
	public function testParseManyValuelessNotes()
	{
		$parsed = $this->parser->parseComment(
			'/**'.PHP_EOL.' * @ab'.PHP_EOL.' * @bc'.PHP_EOL.' * @cd'.PHP_EOL.' */'
		);
		$this->assertEquals(array('ab'=>true, 'bc'=>true, 'cd'=>true), $parsed);
	}

	/**
	 * @group unit
	 * @covers Amiss\Note\Parser::parseComment
	 */
	public function testParseManyNotesWithIrregularMargin()
	{
		$parsed = $this->parser->parseComment(
			'/**'.PHP_EOL.'     * @ab'.PHP_EOL.'*    @bc'.PHP_EOL.' @cd'.PHP_EOL.' */'
		);
		$this->assertEquals(array('ab'=>true, 'bc'=>true, 'cd'=>true), $parsed);
	}

	/**
	 * @group unit
	 * @covers Amiss\Note\Parser::parseComment
	 */
	public function testParsingWorksWhenCommentIsNotDocblock()
	{
		$parsed = $this->parser->parseComment(
			'/*'.PHP_EOL.'     * @ab'.PHP_EOL.'*    @bc'.PHP_EOL.' @cd'.PHP_EOL.' */'
		);
		$this->assertEquals(array('ab'=>true, 'bc'=>true, 'cd'=>true), $parsed);
	}
	
	/**
	 * @group unit
	 * @covers Amiss\Note\Parser::parseComment
	 */
	public function testParsingWorksWhenInputIsNotComment()
	{
		$parsed = $this->parser->parseComment(
			'@ab'.PHP_EOL.'@bc'.PHP_EOL.'@cd'.PHP_EOL
		);
		$this->assertEquals(array('ab'=>true, 'bc'=>true, 'cd'=>true), $parsed);
	}
	
	/**
	 * @group unit
	 * @covers Amiss\Note\Parser::parseComment
	 */
	public function testParsingIgnoresEmailAddresses()
	{
		$parsed = $this->parser->parseComment(
			'If you have problems with this jazz, contact foo@bar.com'.PHP_EOL.
			'@ab'.PHP_EOL.'@bc'.PHP_EOL.'@cd'.PHP_EOL
		);
		$this->assertEquals(array('ab'=>true, 'bc'=>true, 'cd'=>true), $parsed);
	}
}

/**
 * @classNote
 */
class ParserTestClass
{
	/**
	 * @propertyNote
	 */
	public $property;
	
	/**
	 * @methodNote
	 */
	public function method() {}
	
	/**
	 * @methodNote
	 */
	public function casedMethod() {}
}
