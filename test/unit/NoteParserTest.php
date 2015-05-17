<?php
namespace Amiss\Tests\Unit;

/**
 * @group unit
 */
class NoteParserTest extends \CustomTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->parser = new \Amiss\Note\Parser;
    }
    
    /**
     * @covers Amiss\Note\Parser::parseClass
     * @covers Amiss\Note\Parser::parseReflectors
     */
    public function testParseFullClass()
    {
        $info = $this->parser->parseClass(new \ReflectionClass(__NAMESPACE__.'\ParserTestClass'));
        $expected = (object)array(
            'notes'=>array('c1'=>['cv1'=>true], 'c2'=>['cv2'=>true]),
            'methods'=>array(
                'method1'=>array('m1'=>['mv1'=>true], 'm2'=>['mv2'=>true]),
                'method2'=>array('m3'=>['mv3'=>true], 'm4'=>['mv4'=>true]),
            ),
            'properties'=>array(
                'property1'=>array('p1'=>['pv1'=>true], 'p2'=>['pv2'=>true]),
                'property2'=>array('p3'=>['pv3'=>true], 'p4'=>['pv4'=>true]),
            ),
        );

        $this->assertEquals($expected, $info);
    }
    
    /**
     * @covers Amiss\Note\Parser::parse
     */
    public function testParseSingleValuelessNote()
    {
        $parsed = $this->parser->parse(':note = {}');
        $this->assertEquals(array('note'=>[]), $parsed);
    }
 
    /**
     * @covers Amiss\Note\Parser::parse
     */
    public function testParseSingleValueNote()
    {
        $parsed = $this->parser->parse(':note = {"foo": "bar"}');
        $this->assertEquals(array('note'=>["foo"=>"bar"]), $parsed);
    }

    /**
     * @covers Amiss\Note\Parser::parse
     */
    public function testParseManyNotesMultiline()
    {
        $parsed = $this->parser->parse(
            ":one = {}\n".
            ":two = {}\n"
        );
        $this->assertEquals(array("one"=>[], "two"=>[]), $parsed);
    }

    /**
     * @covers Amiss\Note\Parser::stripDocComment
     */
    public function testStripDocCommentMultiline()
    {
        $parsed = $this->parser->stripDocComment(implode("\n", [
            "/**",
            " * foo",
            "   *     bar",
            "baz",
            "*/",
        ]));

        // only the first whitespace after the * is stripped:
        $expected = "foo\n    bar\nbaz";
        $this->assertEquals($expected, $parsed);
    }

    /**
     * @covers Amiss\Note\Parser::stripDocComment
     */
    public function testStripDocCommentFromNonDocblock()
    {
        // /* */ instead of /** */
        $parsed = $this->parser->stripDocComment(
            "/*\n * foo\n*/"
        );
        $this->assertEquals("foo", $parsed);
    }
    
    /**
     * @covers Amiss\Note\Parser::stripDocComment
     */
    public function testStripDocCommentWorksWhenInputIsNotComment()
    {
        $data = "foo\nbar\nbaz";
        $parsed = $this->parser->stripDocComment($data);
        $this->assertEquals($data, $parsed);
    }

    public function testParseIgnoresEscapedKey()
    {
        $in = "\:foo = {}";
        $parsed = $this->parser->parse($in);
        $this->assertEquals([], $parsed);
    }

    public function testParseIgnoresKeyInsideLine()
    {
        $in = "This is how you do it: :foo = {}";
        $parsed = $this->parser->parse($in);
        $this->assertEquals([], $parsed);
    }

    public function testParseAllowsHwspBeforeKey()
    {
        $in = "      \t\t\t    :foo = {}";
        $parsed = $this->parser->parse($in);
        $this->assertEquals(['foo'=>[]], $parsed);
    }

    public function testParseDoubleNameFails()
    {
        $in = ":foo = :bar = {}";
        $this->setExpectedException(
            \Amiss\Note\ParseException::class, 
            "Unexpected token ':bar', expected JSON start '{'"
        );
        $parsed = $this->parser->parse($in);
    }

    /**
     * @dataProvider dataEmptyName
     */
    public function testParseEmptyNameFails($emptyName)
    {
        $in = ": = {}";
        $this->setExpectedException(
            \Amiss\Note\ParseException::class, 
            "Unexpected token '=', expected annotation name"
        );
        $parsed = $this->parser->parse($in);
    }

    function dataEmptyName()
    {
        return [
            [': = {}'],
            [':= {}'],
        ];
    }

    public function testParseEndsExpectingEqualsFailure()
    {
        $in = ":foo";
        $this->setExpectedException(
            \Amiss\Note\ParseException::class, 
            "Unexpected end of definition for key 'foo'"
        );
        $parsed = $this->parser->parse($in);
    }
}

/**
 * :c1 = {"cv1": true}
 * :c2 = {"cv2": true}
 */
class ParserTestClass
{
    /**
     * :p1 = {"pv1": true}
     * :p2 = {"pv2": true}
     */
    public $property1;
    
    /**
     * :p3 = {"pv3": true}
     * :p4 = {"pv4": true}
     */
    public $property2;

    /**
     * :m1 = {"mv1": true}
     * :m2 = {"mv2": true}
     */
    public function method1() {}

    /**
     * :m3 = {"mv3": true}
     * :m4 = {"mv4": true}
     */
    public function method2() {}
}
