<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 09.07.13
 * Time: 09:34
 * To change this template use File | Settings | File Templates.
 */

namespace TechDivision\PBC;

require_once 'PHPUnit/Autoload.php';

require_once __DIR__ . '/BasicTest.php';


class AllTests
{
    public static function suite()
    {
        $suite = new \PHPUnit_Framework_TestSuite();

        $suite->addTestSuite('BasicTest');

        return $suite;
    }
}