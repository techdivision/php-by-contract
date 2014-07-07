<?php
/**
 * File containing the PropertyTest class
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

use TechDivision\PBC\Tests\Data\PropertyTestClass;

/**
 * TechDivision\PBC\Tests\Functional\PropertyTest
 *
 * Will test the invariant enforced attribute access
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
class PropertyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \TechDivision\PBC\Tests\Data\PropertyTestClass $propertyTestClass Our test class
     */
    private $propertyTestClass;

    /**
     * We need the test class from the beginning
     */
    public function __construct()
    {
        $this->propertyTestClass = new PropertyTestClass();
    }

    /**
     * Check if we get a MissingPropertyException
     *
     * @return null
     */
    public function testMissingProperty()
    {
        $e = null;
        try {

            $this->propertyTestClass->notExistingProperty = 'test';

        } catch (\Exception $e) {
        }

        // Did we get the right $e?
        $this->assertInstanceOf("TechDivision\\PBC\\Exceptions\\MissingPropertyException", $e);

        $e = null;
        try {

            $test = $this->propertyTestClass->notExistingProperty;

        } catch (\Exception $e) {
        }

        // Did we get the right $e?
        $this->assertInstanceOf("TechDivision\\PBC\\Exceptions\\MissingPropertyException", $e);
    }

    /**
     * Check if we get an InvalidArgumentException
     *
     * @return null
     */
    public function testPrivateProperty()
    {
        $e = null;
        try {

            $this->propertyTestClass->privateNonCheckedProperty = 'test';

        } catch (\Exception $e) {
        }

        // Did we get the right $e?
        $this->assertInstanceOf("\\InvalidArgumentException", $e);

        $e = null;
        try {

            $test = $this->propertyTestClass->privateNonCheckedProperty;

        } catch (\Exception $e) {
        }

        // Did we get the right $e?
        $this->assertInstanceOf("\\InvalidArgumentException", $e);

        $e = null;
        try {

            $this->propertyTestClass->privateCheckedProperty = 'test';

        } catch (\Exception $e) {
        }

        // Did we get the right $e?
        $this->assertInstanceOf("\\InvalidArgumentException", $e);

        $e = null;
        try {

            $test = $this->propertyTestClass->privateCheckedProperty;

        } catch (\Exception $e) {
        }

        // Did we get the right $e?
        $this->assertInstanceOf("\\InvalidArgumentException", $e);
    }

    /**
     * Check if we get any Exception
     *
     * @return null
     */
    public function testPublicProperty()
    {
        $e = null;
        try {

            $this->propertyTestClass->publicNonCheckedProperty = 'test';

        } catch (\Exception $e) {
        }

        // Did we get the right $e?
        $this->assertNull($e);

        $e = null;
        try {

            $test = $this->propertyTestClass->publicNonCheckedProperty;

        } catch (\Exception $e) {
        }

        // Did we get the right $e?
        $this->assertNull($e);

        $e = null;
        try {

            $this->propertyTestClass->publicCheckedProperty = 27.42;

        } catch (\Exception $e) {
        }

        // Did we get the right $e?
        $this->assertNull($e);

        $e = null;
        try {

            $test = $this->propertyTestClass->publicCheckedProperty;

        } catch (\Exception $e) {
        }

        // Did we get the right $e?
        $this->assertNull($e);
        $this->assertEquals($test, 27.42);

        $e = null;
        try {

            $this->propertyTestClass->publicCheckedProperty = 27.423;

        } catch (\Exception $e) {
        }

        // Did we get the right $e?
        $this->assertInstanceOf("TechDivision\\PBC\\Exceptions\\BrokenInvariantException", $e);
    }
}
