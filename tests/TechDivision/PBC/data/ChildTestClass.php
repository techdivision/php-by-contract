<?php

/**
 * Class ChildTestClass
 *
 * @invariant $this->elements !== null
 */
class ChildTestClass extends ParentTestClass
{
    protected $elements;

    public function size()
    {
        return count($this->elements);
    }

    /**
     *
     */
    public function peek()
    {
        $tmp = array_pop($this->elements);
        array_push($this->elements, $tmp);

        return $tmp;
    }

    /**
     *
     */
    public function pop()
    {
        return array_pop($this->elements);
    }

    /**
     * @ensures in_array($this->elements, $obj)
     */
    public function push(object $obj)
    {
        return array_push($this->elements, $obj);
    }
}
