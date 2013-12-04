<?php

namespace Wicked\salesman\Sales\Stack;

/**
 * Class StringStack
 */
class StringStack extends AbstractStack
{
    /**
     * @ensures is_string($pbcResult)
     */
    public function peek()
    {
        return parent::peek();
    }

    /**
     * @ensures is_string($pbcResult)
     */
    public function pop()
    {
        return parent::pop();
    }

    /**
     * @param   string $obj
     */
    public function push($obj)
    {
        return parent::push($obj);
    }
}