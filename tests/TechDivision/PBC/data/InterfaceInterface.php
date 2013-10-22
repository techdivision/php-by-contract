<?php

namespace Test\InterfaceTest;

/**
 * Interface InterfaceInterface
 *
 * @invariant $this->isConsistent()
 */
interface InterfaceInterface
{
    public function isConsistent();

    public function size();

    /**
     * @requires $this->size() >= 1
     */
    public function peek();

    /**
     * @requires $this->size() >= 1
     * @ensures $this->size() == $this->pbcOld->size() - 1
     * @ensures $pbcResult == $this->pbcOld->peek()
     */
    public function pop();

    /**
     * @ensures $this->size() == $this->pbcOld->size() + 1
     * @ensures $this->peek() == $obj
     */
    public function push($obj);
}