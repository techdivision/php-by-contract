<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 09.07.13
 * Time: 09:34
 * To change this template use File | Settings | File Templates.
 */

namespace TechDivision\PBC\Tests;

use TechDivision\PBC\Config;

require __DIR__ . DIRECTORY_SEPARATOR . 'BasicTest.php';
require __DIR__ . DIRECTORY_SEPARATOR . 'RealTest.php';
require __DIR__ . DIRECTORY_SEPARATOR . 'InheritanceTest.php';
require __DIR__ . DIRECTORY_SEPARATOR . 'StackTest.php';
require __DIR__ . DIRECTORY_SEPARATOR . 'InterfaceTest.php';
require __DIR__ . DIRECTORY_SEPARATOR . 'PropertyTest.php';
require __DIR__ . DIRECTORY_SEPARATOR . 'TypeSafetyTest.php';
require __DIR__ . DIRECTORY_SEPARATOR . 'ParserTest.php';
require __DIR__ . DIRECTORY_SEPARATOR . 'MethodTest.php';
require __DIR__ . DIRECTORY_SEPARATOR . 'GeneratorTest.php';

class AllTests
{
    public static function suite()
    {
        $suite = new \PHPUnit_Framework_TestSuite();

        $suite->addTestSuite('TechDivision\PBC\Tests\BasicTest');
        $suite->addTestSuite('TechDivision\PBC\Tests\RealTest');
        $suite->addTestSuite('TechDivision\PBC\Tests\InheritanceTest');
        $suite->addTestSuite('TechDivision\PBC\Tests\StackTest');
        $suite->addTestSuite('TechDivision\PBC\Tests\InterfaceTest');
        $suite->addTestSuite('TechDivision\PBC\Tests\PropertyTest');
        $suite->addTestSuite('TechDivision\PBC\Tests\ParserTest');
        $suite->addTestSuite('TechDivision\PBC\Tests\MethodTest');
        $suite->addTestSuite('TechDivision\PBC\Tests\GeneratorTest');

        // Basic type safety test only makes sense if we enforce it
        $config = Config::getInstance();
        $enforcementConfig = $config->getConfig('enforcement');
        if ($enforcementConfig['enforce-default-type-safety']) {

            $suite->addTestSuite('TechDivision\PBC\Tests\TypeSafetyTest');
        }

        return $suite;
    }
}
