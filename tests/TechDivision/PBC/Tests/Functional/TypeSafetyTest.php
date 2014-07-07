<?php
/**
 * File containing the TypeSafetyTest class
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

use TechDivision\PBC\Tests\Data\TypeSafetyTestClass;

/**
 * TechDivision\PBC\Tests\Functional\TypeSafetyTest
 *
 * Will test basic type safety
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
class TypeSafetyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \TechDivision\PBC\Tests\Data\TypeSafetyTestClass $typeSafetyTestClass Our test class
     */
    private $typeSafetyTestClass;

    /**
     * Get our class
     */
    public function __construct()
    {
        $this->typeSafetyTestClass = new TypeSafetyTestClass();
    }

    /**
     * Check if we got enforced type safety for params
     *
     * @return null
     */
    public function testBasicPrecondition()
    {
        $e = null;
        try {

            $this->typeSafetyTestClass->iNeedStrings('stringer', 12);

        } catch (\Exception $e) {
        }

        // Did we get the right $e?
        $this->assertInstanceOf("TechDivision\\PBC\\Exceptions\\BrokenPreconditionException", $e);

        $e = null;
        try {

            $this->typeSafetyTestClass->iNeedArrays('test', array());

        } catch (\Exception $e) {
        }

        // Did we get the right $e?
        $this->assertInstanceOf("TechDivision\\PBC\\Exceptions\\BrokenPreconditionException", $e);

        $e = null;
        try {

            $this->typeSafetyTestClass->iNeedNumeric('four');

        } catch (\Exception $e) {
        }

        // Did we get the right $e?
        $this->assertInstanceOf("TechDivision\\PBC\\Exceptions\\BrokenPreconditionException", $e);

        $e = null;
        try {

            $this->typeSafetyTestClass->iNeedStrings('stringer', 'testinger');

        } catch (\Exception $e) {
        }

        // Did we get the right $e?
        $this->assertNull($e);

        $e = null;
        try {

            $this->typeSafetyTestClass->iNeedArrays(array('test', 'test2'), array());

        } catch (\Exception $e) {
        }

        // Did we get the right $e?
        $this->assertNull($e);

        $e = null;
        try {

            $this->typeSafetyTestClass->iNeedNumeric(12, 5);
            $this->typeSafetyTestClass->iNeedNumeric(42);

        } catch (\Exception $e) {
        }

        // Did we get the right $e?
        $this->assertNull($e);
    }

    /**
     * Check if we got enforced type safety for return
     *
     * @return null
     */
    public function testBasicPostcondition()
    {
        $e = null;
        try {

            $this->typeSafetyTestClass->iReturnAString(12);

        } catch (\Exception $e) {
        }

        // Did we get the right $e?
        $this->assertInstanceOf("TechDivision\\PBC\\Exceptions\\BrokenPostconditionException", $e);

        $e = null;
        try {

            $this->typeSafetyTestClass->iReturnAnArray('testinger');

        } catch (\Exception $e) {
        }

        // Did we get the right $e?
        $this->assertInstanceOf("TechDivision\\PBC\\Exceptions\\BrokenPostconditionException", $e);

        $e = null;
        try {

            $this->typeSafetyTestClass->iReturnAnInt(array());

        } catch (\Exception $e) {
        }

        // Did we get the right $e?
        $this->assertInstanceOf("TechDivision\\PBC\\Exceptions\\BrokenPostconditionException", $e);

        $e = null;
        try {

            $this->typeSafetyTestClass->iReturnAnArray();

        } catch (\Exception $e) {
        }

        // Did we get the right $e?
        $this->assertNull($e);

        $e = null;
        try {

            $this->typeSafetyTestClass->iReturnAnInt();

        } catch (\Exception $e) {
        }

        // Did we get the right $e?
        $this->assertNull($e);

        $e = null;
        try {

            $this->typeSafetyTestClass->iReturnAString();

        } catch (\Exception $e) {
        }

        // Did we get the right $e?
        $this->assertNull($e);
    }
}
