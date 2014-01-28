<?php
/**
 * TechDivision\PBC\Tests\Data\InterfaceClass
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 */
namespace TechDivision\PBC\Tests\Data;

/**
 * @package     TechDivision\PBC
 * @subpackage  Tests
 * @copyright   Copyright (c) 2013 <info@techdivision.com> - TechDivision GmbH
 * @license     http://opensource.org/licenses/osl-3.0.php
 *              Open Software License (OSL 3.0)
 * @author      Bernhard Wick <b.wick@techdivision.com>
 */
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
     *
     * @return int
     */
    public function push($obj)
    {
        return array_push($this->elements, $obj);
    }
}
