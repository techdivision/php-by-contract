<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 11.07.13
 * Time: 15:37
 * To change this template use File | Settings | File Templates.
 */

namespace TechDivision\PBC\Interfaces;

interface TypedList
{
    /**
     * @param $value
     * @return mixed
     */
    public function getOffset($value);

    /**
     * Checks if an offset exists.
     *
     * @param mixed $offset
     *
     * @return bool
     */
    public function entryExists($offset);

    /**
     * @param $offset
     * @return mixed
     */
    public function delete($offset);

    /**
     * @param $offset
     *
     * @return mixed
     */
    public function get($offset);

    /**
     * @param $offset
     * @param $value
     *
     * @throws UnexpectedValueException
     */
    public function set($offset, $value);

    /**
     * @return mixed
     *
     * @throws UnexpectedValueException
     */
    public function add($value);

    /**
     * @return ArrayIterator
     */
    public function getIterator();
}