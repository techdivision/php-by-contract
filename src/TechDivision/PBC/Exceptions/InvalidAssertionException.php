<?php

namespace TechDivision\PBC\Exceptions;

use TechDivision\PBC\Interfaces\PBCException;

/**
 * Class InvalidAssertionException
 *
 * This exception might be thrown if a certain precondition gets broken during runtime.
 *
 * @package TechDivision\PBC\Exceptions
 */
class InvalidAssertionException extends \Exception implements PBCException
{

}