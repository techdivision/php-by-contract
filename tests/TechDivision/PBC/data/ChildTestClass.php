<?php

/**
 * Class ChildTestClass
 */
class ChildTestClass extends ParentTestClass
{
    protected $elements;

    public function __construct()
    {
        $this->elements = array();
    }


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
     * @ensures in_array($obj, $this->elements)
     */
    public function push(\Object $obj)
    {
        return array_push($this->elements, $obj);
    }
}
