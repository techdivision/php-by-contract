<?php
/**
 * TechDivision\PBC\Exceptions\ExceptionFactory
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 */

namespace TechDivision\PBC\Exceptions;

/**
 * @package     TechDivision\PBC
 * @subpackage  Exceptions
 * @copyright   Copyright (c) 2013 <info@techdivision.com> - TechDivision GmbH
 * @license     http://opensource.org/licenses/osl-3.0.php
 *              Open Software License (OSL 3.0)
 * @author      Bernhard Wick <b.wick@techdivision.com>
 */
class ExceptionFactory
{
    /**
     * @param $type
     *
     * @return string
     */
    public function getClassName($type)
    {
        return $this->getName($type);
    }

    /**
     * @param       $type
     * @param array $params
     *
     * @return \Exception
     */
    public function getInstance($type, $params = array())
    {
        $name = $this->getName($type);

        return call_user_func_array($name->__construct(), $params);
    }

    /**
     * @param $type
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
