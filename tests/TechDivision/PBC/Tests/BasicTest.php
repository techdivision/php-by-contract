<?php
/**
 * File containing the BasicTest class
 *
 * PHP version 5
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Tests
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 *             Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */

namespace TechDivision\PBC\Tests;

/**
 * TechDivision\PBC\Tests\BasicTest
 *
 * This test is for basic problems like broken type safety or invariant support
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Tests
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 *             Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */
class BasicTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Will check if operations on invariant protected attributes will bring the intended result
     *
     * @return null
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
     * Will test enforcement of type hinting
     *
     * @return null
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
