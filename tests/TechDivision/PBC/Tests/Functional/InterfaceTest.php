<?php
/**
 * File containing the InterfaceTest class
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

use TechDivision\PBC\Tests\Data\InterfaceClass;

/**
 * TechDivision\PBC\Tests\Functional\InterfaceTest
 *
 * Will test basic interface usage
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
class InterfaceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test proper handling of classes which implement an interface
     *
     * @return null
     */
    public function testInstantiation()
    {
        $interfaceClass = new InterfaceClass();
    }

    /**
     * Will test operation on said class
     *
     * @return null
     */
    public function testStackUsage()
    {
        $interfaceClass = new InterfaceClass();

        $someStrings = array('sdfsafsf', 'rzutrzutfzj', 'OUHuISGZduisd0', 'skfse', 'd', 'fdghdfg', 'srfxcf');

        // push the strings into the stack
        foreach ($someStrings as $someString) {

            $interfaceClass->push($someString);
        }
        // and pop some of them again
        $interfaceClass->pop();
        $interfaceClass->pop();
        $interfaceClass->pop();
        $interfaceClass->pop();
        $interfaceClass->pop();
        $interfaceClass->pop();

        $this->assertEquals($interfaceClass->peek(), $interfaceClass->pop());
    }
}
