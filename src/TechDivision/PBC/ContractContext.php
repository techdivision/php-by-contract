<?php
/**
 * TechDivision\PBC\ContractContext
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 */
namespace TechDivision\PBC;

/**
 * @package     TechDivision\PBC
 * @copyright   Copyright (c) 2013 <info@techdivision.com> - TechDivision GmbH
 * @license     http://opensource.org/licenses/osl-3.0.php
 *              Open Software License (OSL 3.0)
 * @author      Bernhard Wick <b.wick@techdivision.com>
 */
class ContractContext
{

    /**
     * @var boolean
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

        }   else {

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

        }   else {

            return false;
        }
    }
} 