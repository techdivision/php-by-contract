<?php
/**
 * File containing the ExceptionFactory class
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
 * TechDivision\PBC\Exceptions\ExceptionFactory
 *
 * Factory to get the right exception object (or class name) for the right occasion.
 * This was implemented to enable custom exception mapping
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
class ExceptionFactory
{
    /**
     * Will return the name of the exception class for the needed error type
     *
     * @param string $type The type of exception we need
     *
     * @return string
     */
    public function getClassName($type)
    {
        return $this->getName($type);
    }

    /**
     * Will return an instance of the exception fitting the error type we specified
     *
     * @param string $type   The type of exception we need
     * @param array  $params Parameter array we will pass to the exception's constructor
     *
     * @return \Exception
     */
    public function getInstance($type, $params = array())
    {
        $name = $this->getName($type);

        return call_user_func_array($name->__construct(), $params);
    }

    /**
     * Will return the name of the Exception class as it is mapped to a certain error type
     *
     * @param string $type The type of exception we need
     *
     * @return string
     */
    private function getName($type)
    {
        // What kind of exception do we need?
        switch ($type) {

            case 'precondition':

                $name = 'BrokenPreconditionException';
                break;

            case 'postcondition':

                $name = 'BrokenPostconditionException';
                break;

            case 'invariant':

                $name = 'BrokenInvariantException';
                break;

            default:

                $name = '\Exception';
                break;
        }

        if (class_exists(__NAMESPACE__ . '\\' . $name)) {

            return __NAMESPACE__ . '\\' . $name;
        }
    }
}
