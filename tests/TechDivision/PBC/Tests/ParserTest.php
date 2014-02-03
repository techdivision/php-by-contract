<?php
/**
 * File containing the ParserTest class
 *
 * PHP version 5
 *
 * @category   php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Tests
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 *             Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */

namespace TechDivision\PBC\Tests;

use TechDivision\PBC\Config;

/**
 * TechDivision\PBC\Tests\ParserTest
 *
 * Will test basic parser usage
 *
 * @category   php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Tests
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 *             Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */
class ParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Will test parsing of special annotations like typed arrays
     *
     * @return null
     */
    public function testAnnotationParsing()
    {
        // Get the object to test
        $annotationTestClass = new Data\AnnotationTestClass();

        $e = null;
        try {

            $annotationTestClass->typeCollection(array(new \Exception(), new \Exception(), new \Exception()));

        } catch (\Exception $e) {
        }

        // Did we get the right $e?
        $this->assertNull($e);

        $e = null;
        try {

            $annotationTestClass->typeCollection(array(new \Exception(), 'failure', new \Exception()));

        } catch (\Exception $e) {
        }

        // Did we get the right $e?
        $this->assertInstanceOf("TechDivision\\PBC\\Exceptions\\BrokenPreconditionException", $e);

        // Get the object to test
        $annotationTestClass = new Data\AnnotationTestClass();

        $e = null;
        try {

            $annotationTestClass->typeCollectionReturn(array(new \Exception(), new \Exception(), new \Exception()));

        } catch (\Exception $e) {
        }

        // Did we get the right $e?
        $this->assertNull($e);

        $e = null;
        try {

            $annotationTestClass->typeCollectionReturn(array(new \Exception(), 'failure', new \Exception()));

        } catch (\Exception $e) {
        }

        // Did we get the right $e?
        $this->assertInstanceOf("TechDivision\\PBC\\Exceptions\\BrokenPostconditionException", $e);

        $e = null;
        try {

            $annotationTestClass->orCombinator(new \Exception());
            $annotationTestClass->orCombinator(null);

        } catch (\Exception $e) {
        }

        // Did we get the right $e?
        $this->assertNull($e);

        $e = null;
        try {

            $annotationTestClass->orCombinator(array());

        } catch (\Exception $e) {
        }

        // Did we get the right $e?
        $this->assertInstanceOf("TechDivision\\PBC\\Exceptions\\BrokenPreconditionException", $e);
    }

    /**
     * Will check for proper method parsing
     *
     * @return null
     */
    public function testMethodParsing()
    {
        $e = null;
        try {

            $methodTestClass = new Data\MethodTestClass();

        } catch (\Exception $e) {
        }

        // Did we get the right $e?
        $this->assertNull($e);
    }

    /**
     * Will test if a configuration using regexed paths can be used properly
     *
     * @return null
     */
    public function testRegexMapping()
    {
        // We have to load the config for regular expressions in the project dirs
        $config = Config::getInstance();
        $config = $config->load(
            __DIR__ . DIRECTORY_SEPARATOR . 'Data' . DIRECTORY_SEPARATOR . 'RegexTest' .
            DIRECTORY_SEPARATOR . 'regextest.conf.json'
        );

        $e = null;
        try {

            $regexTestClass1 = new Data\MultiRegex\A\Data\RegexTestClass1();

        } catch (Exception $e) {
        }

        // Did we get the right $e?
        $this->assertNull($e);

        $e = null;
        try {

            $regexTestClass2 = new Data\MultiRegex\B\Data\RegexTestClass2();

        } catch (\Exception $e) {
        }

        // Did we get the right $e?
        $this->assertNull($e);

        $e = null;
        try {

            $regexTestClass = new Data\RegexTest1\RegexTestClass();

        } catch (\Exception $e) {
        }

        // Did we get the right $e?
        $this->assertNull($e);
    }
}
