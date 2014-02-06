<?php

namespace TechDivision\PBC\Tests\Data\Stack;

/**
 * Class Stack
 *
 * @invariant is_array($this->container)
 */
class AbstractStack
{
    /**
     * @var array
     */
    protected $container = array();

    /**
     * @ensures is_int($pbcResult)
     */
    public function size()
    {
        return count($this->container);
    }

    /**
     * @requires $this->size() >= 1
     * @ensures $this->size() === $pbcOld->size()
     */
    public function peek()
    {
        $tmp = array_pop($this->container);
        array_push($this->container, $tmp);

        return $tmp;
    }

    /**
     * @requires $this->size() >= 1
     * @ensures $this->size() == $pbcOld->size() - 1
     * @ensures $pbcResult == $pbcOld->peek()
     */
    public function pop()
    {
        return array_pop($this->container);
    }

    /**
     * @ensures $this->size() == $pbcOld->size() + 1
     * @ensures $this->peek() == $obj
     */
    public function push($obj)
    {
        return array_push($this->container, $obj);
    }
}
