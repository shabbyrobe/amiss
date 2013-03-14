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
     * @covers Amiss\Note\Parser::parse
     */
    public function testParseSingleValuelessNote()
    {
        $parsed = $this->parser->parse('@ab');
        $this->assertEquals(array('ab'=>true), $parsed);
    }
    
    /**
     * @group unit
     * @covers Amiss\Note\Parser::parse
     */
    public function testParseSingleValuelessNoteWithAlternativeDefaultValue()
    {
        $this->parser->defaultValue = 'ding';
        $parsed = $this->parser->parse('@ab');
        $this->assertEquals(array('ab'=>'ding'), $parsed);
    }
    
    /**
     * @group unit
     * @covers Amiss\Note\Parser::parse
     */
    public function testParseSingleValueNote()
    {
        $parsed = $this->parser->parse('@ab yep');
        $this->assertEquals(array('ab'=>'yep'), $parsed);
    }

    /**
     * @group unit
     * @covers Amiss\Note\Parser::parse
     */
    public function testParseManyValuelessNotes()
    {
        $parsed = $this->parser->parse("@ab\n@bc\n@cd\n");
        $this->assertEquals(array('ab'=>true, 'bc'=>true, 'cd'=>true), $parsed);
    }

    /**
     * @group unit
     * @covers Amiss\Note\Parser::parseDocComment
     */
    public function testParseManyNotesInDocComment()
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
    public function testParseManyNotesInDocCommentWithIrregularMargin()
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
    public function testParsingByDocCommentWorksWhenInputIsNotComment()
    {
        $parsed = $this->parser->parse(
            '@ab'.PHP_EOL.'@bc'.PHP_EOL.'@cd'.PHP_EOL
        );
        $this->assertEquals(array('ab'=>true, 'bc'=>true, 'cd'=>true), $parsed);
    }
    
    /**
     * @group unit
     * @covers Amiss\Note\Parser::parse
     */
    public function testParsingIgnoresEmailAddresses()
    {
        $parsed = $this->parser->parse(
            'If you have problems with this jazz, contact foo@bar.com'.PHP_EOL.
            '@ab'.PHP_EOL.'@bc'.PHP_EOL.'@cd'.PHP_EOL
        );
        $this->assertEquals(array('ab'=>true, 'bc'=>true, 'cd'=>true), $parsed);
    }
    
    public function testNoteBecomesArrayWhenMultipleValuesPresent()
    {
        $parsed = $this->parser->parse(
            "@ab one\n".
            "@ab two\n".
            "@ab three\n"
        );
        $this->assertEquals(array('ab'=>array('one', 'two', 'three')), $parsed);
    }
    
    public function testSingleValueArrayCoercionWithExplicitZeroIndex()
    {
        $parsed = $this->parser->parse("@ab.0 one\n");
        $this->assertEquals(array('ab'=>array('one')), $parsed);
    }
    
    public function testSingleValueArrayCoercionWithEmptyIndex()
    {
        $parsed = $this->parser->parse("@ab. one\n");
        $this->assertEquals(array('ab'=>array('one')), $parsed);
    }
    
    public function testMultipleValueArrayCoercionWithEmptyIndex()
    {
        $parsed = $this->parser->parse(
            "@ab. one\n".
            "@ab. two\n"
        );
        $this->assertEquals(array('ab'=>array('one', 'two')), $parsed);
    }
    
    public function testSingleValueArrayCoercionWithPHPArrayNotation()
    {
        $parsed = $this->parser->parse("@ab[] one\n");
        $this->assertEquals(array('ab'=>array('one')), $parsed);
    }
    
    public function testMultipleValueArrayCoercionWithPHPArrayNotation()
    {
        $parsed = $this->parser->parse(
            "@ab[] one\n".
            "@ab[] two\n"
        );
        $this->assertEquals(array('ab'=>array('one', 'two')), $parsed);
    }
    
    public function testAssoc()
    {
        $parsed = $this->parser->parse(
            "@ab.foo one\n".
            "@ab.bar two\n"
        );
        $expected = array(
            'ab'=>array(
                'foo'=>'one', 
                'bar'=>'two'
            )
        );
        $this->assertEquals($expected, $parsed);
    }
    
    public function testAssocWithPHPArrayNotation()
    {
        $parsed = $this->parser->parse(
            "@ab[foo] one\n".
            "@ab[bar] two\n"
        );
        $expected = array(
            'ab'=>array(
                'foo'=>'one', 
                'bar'=>'two'
            )
        );
        $this->assertEquals($expected, $parsed);
    }
    
    public function testNestedAssoc()
    {
        $parsed = $this->parser->parse(
            "@ab.foo.a one\n".
            "@ab.foo.b two\n".
            "@ab.bar.a three\n"
        );
        $expected = array(
            'ab'=>array(
                'foo'=>array(
                    'a'=>'one',
                    'b'=>'two'
                ),
                'bar'=>array(
                    'a'=>'three'
                )
            )
        );
        $this->assertEquals($expected, $parsed);
    }
    
    public function testNestedAssocWithPHPArrayNotation()
    {
        $parsed = $this->parser->parse(
            "@ab[foo][a] one\n".
            "@ab[foo][b] two\n".
            "@ab[bar][a] three\n"
        );
        $expected = array(
            'ab'=>array(
                'foo'=>array(
                    'a'=>'one',
                    'b'=>'two'
                ),
                'bar'=>array(
                    'a'=>'three'
                )
            )
        );
        $this->assertEquals($expected, $parsed);
    }
    
    public function testMixedNestedAssoc()
    {
        $parsed = $this->parser->parse(
            "@ab.foo[a] one\n".
            "@ab[foo].b two\n".
            "@ab[bar].a three\n"
        );
        $expected = array(
            'ab'=>array(
                'foo'=>array(
                    'a'=>'one',
                    'b'=>'two'
                ),
                'bar'=>array(
                    'a'=>'three'
                )
            )
        );
        $this->assertEquals($expected, $parsed);
    }
    
    public function testInvalidTypeReassignment()
    {
        $this->setExpectedException('UnexpectedValueException', 'Key at path ab already had non-array value, tried to set key foo');
        $parsed = $this->parser->parse(
            "@ab one\n".
            "@ab.foo quack\n"
        );
    }
    
    public function testKeyPrefix()
    {
        $this->parser->keyPrefix = 'amiss.';
        
        $parsed = $this->parser->parse(
            "@amiss.yep\n".
            "@amiss.yep2 pants\n".
            "@nup\n".
            "@amiss.ab.foo[a] one\n".
            "@amiss.ab[foo].b two\n".
            "@amiss.ab[bar].a three\n"
        );
        $expected = array(
            'yep'=>true,
            'yep2'=>'pants',
            'ab'=>array(
                'foo'=>array(
                    'a'=>'one',
                    'b'=>'two'
                ),
                'bar'=>array(
                    'a'=>'three'
                )
            )
        );
        $this->assertEquals($expected, $parsed);
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
