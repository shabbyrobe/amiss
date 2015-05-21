<?php
namespace Amiss\Test\Query;

class CriteriaReplaceFieldsTest extends \CustomMapperTestCase
{
    function testReplace()
    {
        $fields = ['foo'=>['name'=>'bar']];
        $this->assertReplaced('`bar`=?', '{foo}=?', $fields);
    }

    function testReplaceWithTable()
    {
        $fields = ['foo'=>['name'=>'bar', 'table'=>'yep']];
        $this->assertReplaced('`yep`.`bar`=?', '{foo}=?', $fields);
    }

    function testReplaceWithSource()
    {
        $fields = ['foo'=>['name'=>'bar', 'source'=>'pants']];
        $this->assertReplaced('`pants`=?', '{foo}=?', $fields);
    }

    function testReplaceWithTableAndSource()
    {
        $fields = ['foo'=>['name'=>'bar', 'table'=>'yep', 'source'=>'pants']];
        $this->assertReplaced('`yep`.`pants`=?', '{foo}=?', $fields);
    }

    function testReplaceWithTableAlias()
    {
        $fields = ['foo'=>['name'=>'bar']];
        $this->assertReplaced('`alias`.`bar`=?', '{foo}=?', $fields, 'alias');
    }

    function testReplaceWithTableAliasAndTableAndSource()
    {
        $fields = ['foo'=>['name'=>'bar', 'table'=>'yep', 'source'=>'pants']];
        $this->assertReplaced('`yep`.`pants`=?', '{foo}=?', $fields, 'alias');
    }

    function testReplaceTwice()
    {
        $fields = ['foo'=>['name'=>'bar']];
        $this->assertReplaced('`bar`=? OR `bar`=?', '{foo}=? OR {foo}=?', $fields);
    }

    function testReplaceMany()
    {
        $fields = ['foo'=>'hello', 'bar'=>'world'];
        $this->assertReplaced('`hello`=? OR `world`=?', '{foo}=? OR {bar}=?', $fields);
    }

    function testReplaceHappensEverywhere()
    {
        $fields = ['foo'=>'hello', 'bar'=>'world'];
        $this->assertReplaced('`hello`=? OR col="`world`"', '{foo}=? OR col="{bar}"', $fields);
    }

    function testUnsubstitutedFails()
    {
        $meta = new \Amiss\Meta('stdClass', ['fields'=>[]]);
        $this->setExpectedException(\UnexpectedValueException::class, "Unsubstituted tokens left in clause: {foo}");
        $result = \Amiss\Sql\Query\Criteria::replaceFields($meta, "{foo}=?");
    }

    function assertReplaced($expected, $clause, $fields, $tableAlias=null)
    {
        $meta = new \Amiss\Meta('stdClass', ['fields'=>$fields]);
        $result = \Amiss\Sql\Query\Criteria::replaceFields($meta, $clause, $tableAlias);
        $this->assertEquals($expected, $result);
    }
}
