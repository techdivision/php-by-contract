<?php

namespace TechDivision\PBC\Tests\Data\Stack;

/**
 * Class UniqueStack2
 *
 */
class UniqueStack2 extends AbstractStack
{
    /**
     * @requires !in_array($obj, $this->container)
     */
    public function push($obj)
    {
        return parent::push($obj);
    }
}
