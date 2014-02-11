<?php
/**
 * File containing the PreconditionAvailabilityException class
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
 * TechDivision\PBC\Exceptions\PreconditionAvailabilityException
 *
 * This exception might be thrown if a precondition checks assertions that the client does not have influence on.
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
class PreconditionAvailabilityException extends \Exception implements PBCException
{

}
