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
 * Class PreconditionAvailabilityException
 *
 * This exception might be thrown if a precondition checks assertions that the client does not have influence on.
 *
 * @package TechDivision\PBC\Exceptions
 */
class PreconditionAvailabilityException extends \Exception implements PBCException {

}