<?php

namespace Wicked\salesman\Sales\Stack;

/**
 * Class FixedStack
 *
 * @invariant $this->size() <= $this->limit
 */
class FixedStack extends AbstractStack
{
    /**
     * @var int
     */
    protected $limit;

    /**
     * @requires    is_int($_limit)
     */
    public function __construct($_limit)
    {
        $this->limit = $_limit;
    }

    /**
     * @requires $this->size() < $this->limit
     */
    public function push($obj)
    {
        return parent::push($obj);
    }
}