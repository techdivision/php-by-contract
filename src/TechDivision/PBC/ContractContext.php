<?php
/**
 * File containing the ContractContext class
 *
 * PHP version 5
 *
 * @category  Php-by-contract
 * @package   TechDivision\PBC
 * @author    Bernhard Wick <b.wick@techdivision.com>
 * @copyright 2014 TechDivision GmbH - <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php
 *            Open Software License (OSL 3.0)
 * @link      http://www.techdivision.com/
 */

namespace TechDivision\PBC;

/**
 * TechDivision\PBC\ContractContext
 *
 * This class will keep track if there is any contract evaluation going on currently.
 * This is used to prevent endless loops of contracts using userland functions which are contracted themselves
 *
 * @category  Php-by-contract
 * @package   TechDivision\PBC
 * @author    Bernhard Wick <b.wick@techdivision.com>
 * @copyright 2014 TechDivision GmbH - <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php
 *            Open Software License (OSL 3.0)
 * @link      http://www.techdivision.com/
 */
class ContractContext
{

    /**
     * @var boolean $ongoingContract Are we in the middle of an ongoing contract evaluation?
     */
    private static $ongoingContract = false;

    /**
     * Will open a contract context for any current ongoing verification.
     * Will return true if successful (you are the only ongoing contract) and
     * false if there already is something going on.
     *
     * @return bool
     */
    public static function open()
    {
        if (self::$ongoingContract === false) {

            self::$ongoingContract = true;

            return true;

        } else {

            return false;
        }
    }

    /**
     * Is there an ongoing contract?
     *
     * @return bool
     */
    public static function isOngoing()
    {
        return self::$ongoingContract;
    }

    /**
     * Will close an open contract context for the ongoing verification.
     * Will return true if contract was successfully closed and
     * false if there was no contract at all.
     *
     * @return bool
     */
    public static function close()
    {
        if (self::$ongoingContract === true) {

            self::$ongoingContract = false;

            return true;

        } else {

            return false;
        }
    }
}
