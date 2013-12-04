<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 19.06.13
 * Time: 14:27
 * To change this template use File | Settings | File Templates.
 */

namespace TechDivision\PBC\Exceptions;

use TechDivision\PBC\Interfaces\PBCException;

/**
 * Class BrokenPostconditionException
 *
 * This exception might be thrown if a certain postcondition gets broken during runtime.
 *
 * @package TechDivision\PBC\Exceptions
 */
class BrokenPostconditionException extends \Exception implements PBCException
{

}