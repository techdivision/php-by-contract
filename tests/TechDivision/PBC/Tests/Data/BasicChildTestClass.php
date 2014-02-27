<?php
/**
 * File containing the BasicChildTest class
 *
 * PHP version 5
 *
 * @category   Php-by-contract
 * @package    TechDivision_PBC
 * @subpackage Tests
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 *             Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */

namespace TechDivision\PBC\Tests\Data;

/**
 * TechDivision\PBC\Tests\Data\BasicChildTestClass
 *
 * This class has the sole purpose of checking if overwritten methods with different signatures will be handled
 * correctly
 *
 * @category   Php-by-contract
 * @package    TechDivision_PBC
 * @subpackage Tests
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 *             Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */
final class BasicChildTestClass extends BasicTestClass
{
    /**
     * @param integer $param1
     * @param string  $param2
     *
     * @return string
     */
    public function concatSomeStuff($param1, $param2)
    {
        return (string)$param1 . $param2;
    }

    /**
     * @param string $param1
     * @param string $param2
     *
     * @return array
     */
    public function stringToArray($param1, $param2)
    {
        return array($param1 . $param2);
    }
}
