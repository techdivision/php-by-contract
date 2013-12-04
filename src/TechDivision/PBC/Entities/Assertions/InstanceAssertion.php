<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 19.06.13
 * Time: 16:20
 * To change this template use File | Settings | File Templates.
 */

namespace TechDivision\PBC\Entities\Assertions;

/**
 * Class InstanceAssertion
 *
 * This class is used to provide an object base way to pass assertions as e.g. a precondition.
 */
class InstanceAssertion extends AbstractAssertion
{
    /**
     * @var
     */
    public $operand;

    /**
     * @var
     */
    public $class;

    /**
     * @param $_operand
     * @param $_class
     */
    public function __construct($_operand, $_class)
    {
        $this->operand = $_operand;
        $this->class = $_class;
    }

    /**
     * @return bool|string
     */
    public function getString()
    {
        // We need to add an initial backslash if there is none
        if (strpos($this->class, '\\') > 0) {

            $this->class = '\\' . $this->class;
        }

        return (string)$this->operand . ' instanceof ' . $this->class;
    }

    /**
     * @return bool
     */
    public function invert()
    {
        if ($this->inverted !== true) {

            $this->operand = '!' . $this->operand;
            $this->inverted = true;
            return true;

        } elseif ($this->inverted === true) {

            $this->operand = ltrim($this->operand, '!');
            $this->inverted = false;
            return true;

        } else {

            return false;
        }
    }
}