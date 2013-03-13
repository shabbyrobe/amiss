<?php
namespace Amiss\Tests\Unit;

class NoteParserTest extends \CustomTestCase
{
    public function setUp()
    {
        parent::setUp();
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
     * @covers Amiss\Note\Parser::parseDocComment
     */
    public function testParseCondensedValuelessNote()
    {
        $parsed = $this->parser->parseDocComment('/** @ab */');
        $this->assertEquals(array('ab'=>true), $parsed);
    }
    
    /**
     * @group unit
     * @covers Amiss\Note\Parser::parseDocComment
     */
    public function testParseCondensedValueNote()
    {
        $parsed = $this->parser->parseDocComment('/** @ab yep */');
        $this->assertEquals(array('ab'=>'yep'), $parsed);
    }
    
    /**
     * @group unit
     * @covers Amiss\Note\Parser::parseDocComment
     */
    public function testParseSingleValuelessNote()
    {
        $parsed = $this->parser->parseDocComment(
            '/**'.PHP_EOL.' * @ab'.PHP_EOL.' */'
        );
        $this->assertEquals(array('ab'=>true), $parsed);
    }

    /**
     * @group unit
     * @covers Amiss\Note\Parser::parseDocComment
     */
    public function testParseManyValuelessNotes()
    {
        $parsed = $this->parser->parseDocComment(
            '/**'.PHP_EOL.' * @ab'.PHP_EOL.' * @bc'.PHP_EOL.' * @cd'.PHP_EOL.' */'
        );
        $this->assertEquals(array('ab'=>true, 'bc'=>true, 'cd'=>true), $parsed);
    }

    /**
     * @group unit
     * @covers Amiss\Note\Parser::parseDocComment
     */
    public function testParseManyNotesWithIrregularMargin()
    {
        $parsed = $this->parser->parseDocComment(
            '/**'.PHP_EOL.'     * @ab'.PHP_EOL.'*    @bc'.PHP_EOL.' @cd'.PHP_EOL.' */'
        );
        $this->assertEquals(array('ab'=>true, 'bc'=>true, 'cd'=>true), $parsed);
    }

    /**
     * @group unit
     * @covers Amiss\Note\Parser::parseDocComment
     */
    public function testParsingWorksWhenCommentIsNotDocblock()
    {
        $parsed = $this->parser->parseDocComment(
            '/*'.PHP_EOL.'     * @ab'.PHP_EOL.'*    @bc'.PHP_EOL.' @cd'.PHP_EOL.' */'
        );
        $this->assertEquals(array('ab'=>true, 'bc'=>true, 'cd'=>true), $parsed);
    }
    
    /**
     * @group unit
     * @covers Amiss\Note\Parser::parseDocComment
     */
    public function testParsingWorksWhenInputIsNotComment()
    {
        $parsed = $this->parser->parseDocComment(
            '@ab'.PHP_EOL.'@bc'.PHP_EOL.'@cd'.PHP_EOL
        );
        $this->assertEquals(array('ab'=>true, 'bc'=>true, 'cd'=>true), $parsed);
    }
    
    /**
     * @group unit
     * @covers Amiss\Note\Parser::parseDocComment
     */
    public function testParsingIgnoresEmailAddresses()
    {
        $parsed = $this->parser->parseDocComment(
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
