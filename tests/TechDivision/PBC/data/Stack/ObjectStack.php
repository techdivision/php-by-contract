<?php

namespace Wicked\salesman\Sales\Stack;

/**
 * Class ObjectStack
 */
class ObjectStack extends AbstractStack
{
    /**
     * @ensures $pbcResult instanceof \Object
     */
    public function peek()
    {
        return parent::peek();
    }

    /**
     * @ensures $pbcResult instanceof \Object
     */
    public function pop()
    {
        return parent::pop();
    }

    /**
     * @requires is_object($obj)
     */
    public function push($obj)
    {
        return parent::push($obj);
    }
}