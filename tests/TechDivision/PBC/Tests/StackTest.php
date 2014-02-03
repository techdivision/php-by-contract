<?php
/**
 * File containing the StackTest class
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
 * TechDivision\PBC\Tests\StackTest
 *
 * Will test with the well known stack example
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
class StackTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Get the test and scoop around in the stack
     *
     * @return null
     */
    public function testBuild()
    {
        // Get the object to test
        $stackSale = new Data\Stack\StackSale();
        $stackSale->sell();
    }
}
