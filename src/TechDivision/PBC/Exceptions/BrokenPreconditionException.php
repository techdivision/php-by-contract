<?php
/**
 *
 */
namespace TechDivision\PBC\Exceptions;

use TechDivision\PBC\Interfaces\PBCException;

/**
 * Class BrokenPreconditionException
 *
 * This exception might be thrown if a certain precondition gets broken during runtime.
 *
 * @package TechDivision\PBC\Exceptions
 */
class BrokenPreconditionException extends \Exception implements PBCException {

}