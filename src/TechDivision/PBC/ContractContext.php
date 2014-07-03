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
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
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
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      http://www.techdivision.com/
 */
class ContractContext
{

    /**
     * @var int $contractDepth At which depth are we in the middle of an ongoing contract evaluation?
     */
    private static $contractDepth = 0;

    /**
     * @var int $maxDepth The maximum depth we allow. This will be set from configuration at first call
     */
    private static $maxDepth;

    /**
     * Will open a contract context for any current ongoing verification.
     * Will return true if successful (you are the only ongoing contract) and
     * false if there already is something going on.
     *
     * @return bool
     */
    public static function open()
    {
        // If the max contract depth is not set we have to get it from configuration
        if (!isset(self::$maxDepth)) {

            self::fetchMaxDepth();
        }

        if (self::$contractDepth < self::$maxDepth) {

            self::$contractDepth++;

            return true;

        } else {

            return false;
        }
    }

    /**
     * Is there an ongoing contract beyond the maximal depth?
     *
     * @return bool
     */
    public static function isOngoing()
    {
        return !(self::$contractDepth <= self::$maxDepth);
    }

    /**
     * Will close an open contract context for the ongoing verification.
     * Will return true if contract was successfully closed and
     * false if there was no contract at all.
     *
     * @throws \Exception
     *
     * @return bool
     */
    public static function close()
    {
        if (self::$contractDepth <= self::$maxDepth && self::$contractDepth > 0) {

            // Decrement the used depth
            self::$contractDepth--;

            return true;

        } else {

            // Did we reach a place where the sun does not shine (metaphorically speaking ;-)
            if (self::$contractDepth < 0) {

                // Reset the used up contract depth and fail
                self::$contractDepth = 0;
                throw new \Exception('Contract depth surveillance ran out of bounds!');
            }

            return false;
        }
    }

    /**
     * This will set the maximum depth to which contracts can be nested.
     * Default should be 15 which should be fine for most applications.
     *
     * @throws \Exception
     *
     * @return null
     */
    public static function fetchMaxDepth()
    {
        // Get the config instance
        $config = Config::getInstance();
        $config = $config->getConfig('enforcement');

        if (isset($config['max-nesting'])) {

            self::$maxDepth = $config['max-nesting'];

        } else {

            // Reset the used up contract depth and fail
            self::$contractDepth = 0;
            throw new \Exception('We got not max-nesting configuration! Consult documentation and change this.');
        }
    }

    /**
     * This will set the maximum depth to which contracts can be nested.
     * CAUTION: DO NOT SET THIS TOO HIGH!
     * Default is 15 which should be fine for most applications
     *
     * @param int $maxDepth The maximum depth we allow
     *
     * @return null
     */
    public static function setMaxDepth($maxDepth)
    {
        self::$maxDepth = $maxDepth;
    }
}
