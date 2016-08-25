<?php
namespace Amiss\Test\Unit;

use Amiss\Test;

/**
 * @group unit
 * @group mapper
 */
class ChainMapperTest extends \Amiss\Test\Helper\TestCase
{
    function setUp()
    {
        $this->chainMapper = new \Amiss\Mapper\Chain([
            $this->mapper1 = $this->getMockBuilder(\Amiss\Mapper::class)->getMockForAbstractClass(),
            $this->mapper2 = $this->getMockBuilder(\Amiss\Mapper::class)->getMockForAbstractClass(),
        ]);
    }

    function dataTestProxyMapper()
    {
        $meta = new \Amiss\Meta('a', []);
        return [
            ['formatParams'           , [$meta                   , []                     , []]]          ,
            ['getMeta'                , ['pants']                , $meta]                 ,
            ['mapObjectToProperties'  , [(object)['a'=>'pants']  , $meta]]                ,
            ['mapObjectsToProperties' , [[(object)['a'=>'pants'] , (object)['a'=>'trou']] , $meta]]       ,
            ['mapObjectToRow'         , [(object)['a'=>'a']      , $meta                  , 'context']]   ,
            ['mapObjectsToRows'       , [[(object)['a'=>'a']     , (object)['a'=>'b']]    , $meta         , 'context']] ,
            ['mapPropertiesToRow'     , [$meta                   , (object)['a'=>'a']]]   ,
            ['mapRowToObject'         , [$meta                   , ['a'=>'a']]]           ,
            ['mapRowsToObjects'       , [$meta                   , [['a'=>'a']            , ['a'=>'b']]]] ,
            ['mapRowToProperties'     , [$meta                   , ['a'=>'a']]]           ,
            ['createObject'           , [$meta                   , []                     , []]]          ,
            ['populateObject'         , [(object)[]              , (object)['a'=>'a']     , $meta]]       ,
        ];
    }

    /** @dataProvider dataTestProxyMapper */
    function testProxyMapper1($method, $args, $return=null)
    {
        if (func_num_args() == 2) {
            $return = new \stdClass;
        }

        $this->mapper1->expects($this->any())  ->method('canMap')->will($this->returnValue(true));
        $this->mapper1->expects($this->any())  ->method($method)->with(...$args)->will($this->returnValue($return));
        $this->mapper2->expects($this->never())->method($method);
        $out = $this->chainMapper->$method(...$args);
        $this->assertEquals($return, $out);
    }

    /** @dataProvider dataTestProxyMapper */
    function testProxyMapper2($method, $args, $return=null)
    {
        if (func_num_args() == 2) {
            $return = new \stdClass;
        }

        $this->mapper2->expects($this->any())  ->method('canMap')->will($this->returnValue(true));
        $this->mapper2->expects($this->any())  ->method($method)->with(...$args)->will($this->returnValue($return));
        $this->mapper1->expects($this->never())->method($method);
        $out = $this->chainMapper->$method(...$args);
        $this->assertEquals($return, $out);
    }
}
