<?php
/**
 * File containing the Assertion interface
 *
 * PHP version 5
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Interfaces
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */

namespace TechDivision\PBC\Interfaces;

/**
 * TechDivision\PBC\Interfaces\Assertion
 *
 * An interface defining the functionality of all assertion classes
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Interfaces
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */
interface AssertionInterface
{
    /**
     * Will return an inverted string representation.
     * Implemented here, as we want to check if there is an entry in our inversion map we can use
     *
     * @return string
     */
    public function getInvertString();

    /**
     * Will return a string representation of this assertion
     *
     * @return string
     */
    public function getString();

    /**
     * Invert the logical meaning of this assertion
     *
     * @return boolean
     */
    public function invert();

    /**
     * Will return true if the assertion is in an inverted state
     *
     * @return boolean
     */
    public function isInverted();

    /**
     * Will return true if the assertion is only usable within a private context.
     *
     * @return boolean
     */
    public function isPrivateContext();

    /**
     * Will test if the assertion will result in a valid PHP statement
     *
     * @return boolean
     */
    public function isValid();

    /**
     * Setter for the $privateContext attribute
     *
     * @param boolean $privateContext The value to set the private context to
     *
     * @return void
     */
    public function setPrivateContext($privateContext);
}
