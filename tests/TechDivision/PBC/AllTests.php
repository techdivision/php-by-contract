<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 09.07.13
 * Time: 09:34
 * To change this template use File | Settings | File Templates.
 */

namespace TechDivision\PBC;

require __DIR__ . '/BasicTest.php';
require __DIR__ . '/RealTest.php';
require __DIR__ . '/InheritanceTest.php';
require __DIR__ . '/StackTest.php';
require __DIR__ . '/InterfaceTest.php';

class AllTests
{
    public static function suite()
    {
        $suite = new \PHPUnit_Framework_TestSuite();

        $suite->addTestSuite('BasicTest');
        $suite->addTestSuite('RealTest');
        $suite->addTestSuite('InheritanceTest');
        $suite->addTestSuite('StackTest');
        $suite->addTestSuite('InterfaceTest');

        return $suite;
    }
}