<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 20.06.13
 * Time: 14:47
 * To change this template use File | Settings | File Templates.
 */

namespace TechDivision\PBC\Entities;

abstract class TypedList implements ArrayAccess, IteratorAggregate
{
    protected $itemType;

    protected $container = array();

    /**
     * Checks if an offset exists.
     *
     * @param mixed $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->container[$offset]);
    }

    /**
     * @param $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->container[$offset]);
    }

    /**
     * @param $offset
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->container[$offset];
    }

    /**
     * @param $offset
     * @param $value
     *
     * @throws UnexpectedValueException
     */
    public function offsetSet($offset, $value)
    {
        if (!is_a($value, $this->itemType)) {
            throw new UnexpectedValueException();
        }
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }

    /**
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->container);
    }
}