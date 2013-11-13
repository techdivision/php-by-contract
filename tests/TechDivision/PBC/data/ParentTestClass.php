<?php

/**
 * Class Stack
 *
 * @invariant $this->size() >= 0
 * @invariant $this->size() < 100
 */
class ParentTestClass
{
    public function size()
    {

    }

    /**
     * @requires $this->size() >= 1
     */
    public function peek()
    {

    }

    /**
     * @requires $this->size() >= 1
     * @ensures $this->size() == $pbcOld->size() - 1
     * @ensures $pbcResult == $pbcOld->peek()
     */
    public function pop()
    {

    }

    /**
     * @ensures $this->size() == $pbcOld->size() + 1
     * @ensures $this->peek() == $obj
     */
    public function push(object $obj)
    {

    }
}