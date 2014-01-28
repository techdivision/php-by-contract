<?php

namespace TechDivision\PBC\Tests\Data\Stack;

/**
 * Class UniqueStack1
 *
 * @invariant $this->isValid()
 */
class UniqueStack1 extends AbstractStack
{
    /**
     * @return bool
     */
    private function isValid()
    {
        if (count($this->container) !== count(array_unique($this->container))) {

            return false;

        } else {

            return true;
        }
    }
}
