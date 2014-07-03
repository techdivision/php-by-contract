<?php
/**
 * File containing the AbstractLockableEntity class
 *
 * PHP version 5
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Entities
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */

namespace TechDivision\PBC\Entities;

/**
 * TechDivision\PBC\Entities\AbstractLockableEntity
 *
 * Abstract class for lockable entities.
 * Lockable means, that write access to child class properties is handled via magic setter and can be switched off
 * (but not switched of again) via the lock() method.
 * This is used to implement entities based on the DTO pattern (immutable) + the possibility to set attributes
 * dynamically during a more complex creation procedure.
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Entities
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */
abstract class AbstractLockableEntity
{
    /**
     * @var bool $isLocked Flag for locking the entity to make it immutable
     */
    protected $isLocked = false;

    /**
     * Will set the child classes properties if the entity is not locked
     *
     * @param string $attribute The name of the attribute we want to set
     * @param mixed  $value     The value we want to assign to it
     *
     * @return null
     * @throws \IllegalArgumentException
     * @throws \IllegalAccessException
     */
    public function __set($attribute, $value)
    {
        // If we are locked tell them
        if ($this->isLocked) {

            throw new \IllegalAccessException('The entity ' . get_called_class() . ' is in a locked state');
        }

        // If we do not have this property we should tell them
        if (!property_exists($this, $attribute)) {

            throw new \IllegalArgumentException('There is no attribute called ' . $attribute);
        }

        // Still here? Set it then
        $this->$attribute = $value;
    }

    /**
     * Will call the child's method with the passed arguments as long as the entity is not locked
     *
     * @param string $name      The name of the method we want to set
     * @param array  $arguments The arguments to the method
     *
     * @return null
     * @throws \IllegalArgumentException
     * @throws \IllegalAccessException
     */
    public function __call($name, array $arguments)
    {
        // If we are locked tell them
        if ($this->isLocked) {

            throw new \IllegalAccessException('The entity ' . get_called_class() . ' is in a locked state');
        }

        // If we do not have this method we should tell them
        if (!method_exists($this, $name)) {

            throw new \IllegalArgumentException('There is no method called ' . $name);
        }

        // Still here? call the method then
        call_user_func_array(array($this, $name), $arguments);
    }

    /**
     * Will lock the child entity and make it immutable (if there are no other means of access)
     *
     * @return null
     */
    public function lock()
    {
        $this->isLocked = true;
    }
}
