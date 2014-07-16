<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @category   Php-by-contract
 * @package    TechDivision_PBC
 * @subpackage Utils
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */

namespace TechDivision\PBC\Utils;

/**
 * TechDivision\PBC\Utils\InstanceContainer
 *
 * Provides a static container to provide instances we want to inject into generated code
 *
 * @category   Php-by-contract
 * @package    TechDivision_PBC
 * @subpackage Utils
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */
class InstanceContainer implements \ArrayAccess
{
    /**
     * The actual container where instances are stored
     *
     * @var array $container
     */
    protected static $container = array();

    /**
     * Will check if an offset exists within the container
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     *
     * @param mixed $offset The offset to check for
     *
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return isset(self::$container[$offset]);
    }

    /**
     * Returns a value at a certain offset
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     *
     * @param mixed $offset The offset to get
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return self::$container[$offset];
    }

    /**
     * Sets a value at a certain offset
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     *
     * @param mixed $offset Offset to save the value at
     * @param mixed $value  The value to set at the given offset
     *
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        self::$container[$offset] = $value;
    }

    /**
     * Unsets the value at the given offset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     *
     * @param mixed $offset The offset at which to unset
     *
     * @return void
     */
    public function offsetUnset($offset)
    {
        // Only offset if there even is a value
        if ($this->offsetExists($offset)) {

            unset(self::$container[$offset]);
        }
    }
}
