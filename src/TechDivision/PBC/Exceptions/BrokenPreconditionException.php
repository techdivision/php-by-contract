<?php
/**
 * File containing the BrokenPreconditionException class
 *
 * PHP version 5
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Exceptions
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */

namespace TechDivision\PBC\Exceptions;

use TechDivision\PBC\Interfaces\PBCExceptionInterface;

/**
 * TechDivision\PBC\Exceptions\BrokenPreconditionException
 *
 * This exception might be thrown if a certain precondition gets broken during runtime.
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Exceptions
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */
class BrokenPreconditionException extends \Exception implements PBCExceptionInterface
{

}
