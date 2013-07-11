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
 * Class SubstitutionPrincipleException
 *
 * This exception may be thrown, if a defined contract breaks the Liskov's substitution principle.
 *
 * @package TechDivision\PBC\Exceptions
 */
class SubstitutionPrincipleException extends \Exception implements PBCException {

}