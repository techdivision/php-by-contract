<?php
/**
 * TechDivision\PBC\Entities\Lists\AbstractTypedList
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 */

namespace TechDivision\PBC\Entities\Lists;

use TechDivision\PBC\Interfaces\TypedListInterface;

/**
 * @package     TechDivision\PBC
 * @subpackage  Entities
 * @copyright   Copyright (c) 2013 <info@techdivision.com> - TechDivision GmbH
 * @license     http://opensource.org/licenses/osl-3.0.php
 *              Open Software License (OSL 3.0)
 * @author      Bernhard Wick <b.wick@techdivision.com>
 */
abstract class AbstractTypedList implements TypedListInterface
{
    /**
     * @var string
     */
    protected $itemType;

    /**
     * @var array
     */
    protected $container = array();

    /**
     * @var string
     */
    protected $defaultOffset = '';

    /**
     * Checks if the list is empty
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->container);
    }

    /**
     * @param $value
     * @return bool|mixed
     */
    public function getOffset($value)
    {
        $iterator = $this->getIterator();
        for ($i = 0; $i < $iterator->count(); $i++) {

            if ($iterator->current() === $value) {

                return true;
            }

            // Move the iterator
            $iterator->next();
        }

        // We found nothing
        return false;
    }

    /**
     * Checks if an offset exists.
     *
     * @param mixed $offset
     *
     * @return bool
     */
    public function entryExists($offset)
    {
        return isset($this->container[$offset]);
    }

    /**
     * @param $offset
     * @return mixed|void
     */
    public function delete($offset)
    {
        unset($this->container[$offset]);
    }

    /**
     * @param $offset
     *
     * @return mixed
     */
    public function get($offset)
    {
        if (isset($this->container[$offset])) {

            return $this->container[$offset];

        } else {

            return false;
        }
    }

    /**
     * @param $offset
     * @param $value
     * @return \TechDivision\PBC\Interfaces\UnexpectedValueException|void
     * @throws \UnexpectedValueException
     */
    public function set($offset, $value)
    {
        if (!is_a($value, $this->itemType)) {

            throw new \UnexpectedValueException();

        } else {

            $this->container[$offset] = $value;
        }
    }

    /**
     * @param $value
     * @return mixed|void
     * @throws \UnexpectedValueException
     */
    public function add($value)
    {
        if (!is_a($value, $this->itemType)) {

            throw new \UnexpectedValueException();

        } else {

            $tmp = $this->defaultOffset;
            if (property_exists($value, $tmp)) {

                $this->container[$value->$tmp] = $value;

            } else {

                $this->container[] = $value;
            }
        }
    }

    /**
     * @param TypedList $foreignList
     * @throws \Exception
     * @throws \UnexpectedValueException
     */
    public function attach(TypedList $foreignList)
    {
        $iterator = $foreignList->getIterator();
        for ($i = 0; $i < $iterator->count(); $i++) {

            try {

                $this->add($iterator->current());

            } catch (\UnexpectedValueException $e) {

                throw $e;
            }

            $iterator->next();
        }
    }

    /**
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->container);
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->container);
    }
}