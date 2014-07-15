<?php
/**
 * File containing the ParserTest class
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

namespace TechDivision\PBC\Tests\Functional;

use TechDivision\PBC\Config;
use TechDivision\PBC\Tests\Data\AnnotationTestClass;
use TechDivision\PBC\Tests\Data\MethodTestClass;
use TechDivision\PBC\Tests\Data\MultiRegex\A\Data\RegexTestClass1;
use TechDivision\PBC\Tests\Data\MultiRegex\B\Data\RegexTestClass2;
use TechDivision\PBC\Tests\Data\RegexTest1\RegexTestClass;

/**
 * TechDivision\PBC\Tests\Functional\ParserTest
 *
 * Will test basic parser usage
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
        $annotationTestClass = new AnnotationTestClass();

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
        $annotationTestClass = new AnnotationTestClass();

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

            $methodTestClass = new MethodTestClass();

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
        $config = new Config();
        $config->load(
            str_replace(DIRECTORY_SEPARATOR . 'Functional', '', __DIR__) . DIRECTORY_SEPARATOR . 'Data' . DIRECTORY_SEPARATOR . 'RegexTest' .
            DIRECTORY_SEPARATOR . 'regextest.conf.json'
        );

        $e = null;
        try {

            $regexTestClass1 = new RegexTestClass1();

        } catch (Exception $e) {
        }

        // Did we get the right $e?
        $this->assertNull($e);

        $e = null;
        try {

            $regexTestClass2 = new RegexTestClass2();

        } catch (\Exception $e) {
        }

        // Did we get the right $e?
        $this->assertNull($e);

        $e = null;
        try {

            $regexTestClass = new RegexTestClass();

        } catch (\Exception $e) {
        }

        // Did we get the right $e?
        $this->assertNull($e);
    }
}
