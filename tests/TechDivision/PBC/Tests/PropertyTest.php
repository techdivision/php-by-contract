<?php
/**
 * TechDivision\PBC\Tests\PropertyTest
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
class PropertyTest extends \PHPUnit_Framework_TestCase
{

    private $propertyTestClass;

    /**
     *
     */
    public function __construct()
    {
        $this->propertyTestClass = new Data\PropertyTestClass();
    }

    /**
     * Check if we get a MissingPropertyException
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
