<?php
/**
 * File containing the StreamFilter interface
 *
 * PHP version 5
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Interfaces
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 *             Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */

namespace TechDivision\PBC\Interfaces;

/**
 * TechDivision\PBC\Interfaces\StreamFilter
 *
 * An interface defining the functionality of any possible stream filter class
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Interfaces
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 *             Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 *
 * TODO enforce naming conventions to make this StreamFilterInterface
 */
interface StreamFilter
{
    /**
     * Will return the order number the concrete filter has been constantly assigned
     *
     * @return int
     */
    public function getFilterOrder();

    /**
     * Will return true if all dependencies for this filter were met.
     * This mostly means that needed filters are appended in front of this one
     *
     * @return boolean
     */
    public function dependenciesMet();
}
