<?php
/**
 * File containing the AbstractLockableEntity class
 *
 * PHP version 5
 *
 * @category   php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Entities
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 *             Open Software License (OSL 3.0)
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
 * @category   php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Entities
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 *             Open Software License (OSL 3.0)
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
     * @param $attribute
     * @param $value
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
     * Will lock the child entity and make it immutable (if there are no other means of access)
     *
     * @return null
     */
    public function lock()
    {
        $this->isLocked = true;
    }
}
