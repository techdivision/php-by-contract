<?php
/**
 * File containing the DefinitionInterface interface
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
 * TechDivision\PBC\Interfaces\DefinitionInterface
 *
 * An interface defining the minimal functionality of any possible definition class
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Interfaces
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */
interface DefinitionInterface
{
    /**
     * Will return a string representation of this definition
     *
     * @return string
     */
    public function getString();
}
