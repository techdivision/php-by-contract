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
 * Class BrokenInvariantException
 *
 * This exception might be thrown if a certain invariant gets broken during runtime.
 *
 * @package TechDivision\PBC\Exceptions
 */
class BrokenInvariantException extends \Exception implements PBCException
{

}
