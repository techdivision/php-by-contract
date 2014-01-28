<?php
/**
 * TechDivision\PBC\Tests\BasicTest
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 */
namespace TechDivision\PBC\Tests;

require_once 'PHPUnit/Autoload.php';
require_once __DIR__ . "/../../../../src/TechDivision/PBC/Bootstrap.php";

/**
 * @package     TechDivision\PBC
 * @subpackage  Tests
 * @copyright   Copyright (c) 2013 <info@techdivision.com> - TechDivision GmbH
 * @license     http://opensource.org/licenses/osl-3.0.php
 *              Open Software License (OSL 3.0)
 * @author      Bernhard Wick <b.wick@techdivision.com>
 */
class BasicTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     */
    public function testInvariantBreaks()
    {
        // Get the object to test
        $test = new Data\BasicTestClass();

        // This one should not break
        $test->iDontBreakTheInvariant();

        $e = null;
        try {

            $test->iBreakTheInvariant();

        } catch (\Exception $e) {
        }

        // Did we get the right $e?
        $this->assertInstanceOf("TechDivision\\PBC\\Exceptions\\BrokenInvariantException", $e);
    }

    /**
     *
     */
    public function testParamTyping()
    {
        // Get the object to test
        $test = new Data\BasicTestClass();

        // These tests should all be successful
        $test->stringToArray("null");
        $test->concatSomeStuff(17, 'test', new \Exception());
        $test->stringToWelcome('stranger');


        // These should all fail
        $e = null;
        try {

            $test->stringToArray(13);

        } catch (\Exception $e) {
        }

        // Did we get the right $e?
        $this->assertInstanceOf("TechDivision\\PBC\\Exceptions\\BrokenPreconditionException", $e);

        $e = null;
        try {

            $test->concatSomeStuff("26", array(), new \Exception());

        } catch (\Exception $e) {
        }

        // Did we get the right $e?
        $this->assertInstanceOf("TechDivision\\PBC\\Exceptions\\BrokenPreconditionException", $e);

        $e = null;
        try {

            $test->stringToWelcome(34);

        } catch (\Exception $e) {
        }

        // Did we get the right $e?
        $this->assertInstanceOf("TechDivision\\PBC\\Exceptions\\BrokenPreconditionException", $e);
    }
}
