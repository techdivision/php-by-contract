<?php
/**
 * File containing the BrokenPostconditionException class
 *
 * PHP version 5
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Exceptions
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 *             Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */

namespace TechDivision\PBC\Exceptions;

use TechDivision\PBC\Interfaces\PBCException;

/**
 * TechDivision\PBC\Exceptions\BrokenPostconditionException
 *
 * This exception might be thrown if a certain postcondition gets broken during runtime.
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Exceptions
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 *             Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */
class BrokenPostconditionException extends \Exception implements PBCException
{

}
