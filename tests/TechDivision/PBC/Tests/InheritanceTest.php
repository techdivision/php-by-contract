<?php
/**
 * File containing the InheritanceTest class
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

/**
 * TechDivision\PBC\Tests\InheritanceTest
 *
 * This test covers issues with inheritance of contracts
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
class InheritanceTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Will test if inheritance of precondition works over one level (class to class)
     *
     * @return null
     */
    public function testInheritance()
    {
        $testClass = new Data\ChildTestClass();


        // These should fail
        $e = null;
        try {

            $testClass->pop();

        } catch (\Exception $e) {
        }

        // Did we get the right $e?
        $this->assertInstanceOf("TechDivision\\PBC\\Exceptions\\BrokenPreconditionException", $e);

    }
}
