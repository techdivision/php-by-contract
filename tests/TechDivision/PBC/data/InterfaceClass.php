<?php

namespace Test\InterfaceTest;

class InterfaceClass implements InterfaceInterface
{
    private $elements = array();

    /**
     * @return bool
     */
    public function isConsistent()
    {
        return is_array($this->elements);
    }

    /**
     * @return int
     */
    public function size()
    {
        return count($this->elements);
    }

    /**
     * @return mixed
     */
    public function peek()
    {
        $tmp = array_pop($this->elements);
        array_push($this->elements, $tmp);

        return $tmp;
    }

    /**
     * @return mixed
     */
    public function pop()
    {
        return array_pop($this->elements);
    }

    /**
     * @param $obj
     * @return int
     */
    public function push($obj)
    {
        return array_push($this->elements, $obj);
    }
}