<?php
/**
 * File containing the BrokenInvariantException class
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
 * TechDivision\PBC\Exceptions\BrokenInvariantException
 *
 * This exception might be thrown if a certain invariant gets broken during runtime.
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Exceptions
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */
class BrokenInvariantException extends \Exception implements PBCExceptionInterface
{

}
