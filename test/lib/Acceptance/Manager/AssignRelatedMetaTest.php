<?php
namespace Amiss\Test\Acceptance;

use Amiss\Test;

/**
 * @group acceptance
 * @group manager
 */
class AssignRelatedMetaTest extends \Amiss\Test\Helper\TestCase
{
    function setUp()
    {
        $map = [
            'Pants'=>[
                'class'  => 'stdClass',
                'table'  => 'pants',
                'fields' => [
                    'id' => ['primary' => true, 'type' => 'autoinc'],
                ],
                'relations' => [
                    'other' => ['one', 'of' => 'Other']
                ],
            ],
            'Other'=>[
                'class'  => 'stdClass',
                'table'  => 'other',
                'fields' => [
                    'id' => ['primary' => true, 'type' => 'autoinc'],
                ],
            ],
        ];
        $map['Trou'] = $map['Pants'];
        $map['Trou']['table'] = 'trou';
        unset($map['Trou']['relations']);
        $this->deps = Test\Factory::managerArraysModelCustom($map);

        $manager = $this->deps->manager;
        $manager->insertTable('Other', ['id'=>1]);
        $manager->insertTable('Trou' , ['id'=>1]);
    }

    function testMetaObject()
    {
        $manager = $this->deps->manager;
        $trou = $manager->getById('Trou', 1);
        $manager->assignRelated($trou, 'other', $manager->getMeta('Pants'));
        $this->assertEquals((object)['id'=>1, 'other'=>(object)['id'=>1]], $trou);
    }

    function testMetaString()
    {
        $manager = $this->deps->manager;
        $trou = $manager->getById('Trou', 1);
        $manager->assignRelated($trou, 'other', 'Pants');
        $this->assertEquals((object)['id'=>1, 'other'=>(object)['id'=>1]], $trou);
    }

    function testMetaDodgy()
    {
        $manager = $this->deps->manager;
        $trou = $manager->getById('Trou', 1);
        $this->setExpectedException(\InvalidArgumentException::class, "Unexpected type for id: array");
        $manager->assignRelated($trou, 'other', []);
    }
}
