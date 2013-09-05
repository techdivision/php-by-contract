<?php

namespace Wicked\salesman\Sales\Stack;

/**
 * Class Stack
 *
 * @invariant is_array($this->container)
 */
abstract class AbstractStack
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
     * @ensures $this->size() === $this->pbcOld->size()
     */
    public function peek()
    {
        $tmp = $this->pop();
        $this->push($tmp);

        return $tmp;
    }

    /**
     * @requires $this->size() >= 1
     * @ensures $this->size() == $this->pbcOld->size() - 1
     * @ensures $pbcResult == $this->pbcOld->peek()
     */
    public function pop()
    {
        return array_pop($this->container);
    }

    /**
     * @ensures $this->size() == $this->pbcOld->size() + 1
     * @ensures $this->peek() == $obj
     */
    public function push($obj)
    {
        return array_push($this->container, $obj);
    }
}