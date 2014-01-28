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
 * Class TypeAssertion
 *
 * This class is used to provide an object base way to pass assertions as e.g. a precondition.
 */
class TypeAssertion extends AbstractAssertion
{
    /**
     * @var
     */
    public $operand;

    /**
     * @var
     */
    public $type;

    /**
     * @var
     */
    public $validatesTo;

    /**
     *
     * @param string $_operand
     * @param        $_type
     */
    public function __construct($_operand, $_type)
    {
        $this->operand = $_operand;
        $this->validatesTo = true;
        $this->type = $_type;

        parent::__construct();
    }

    /**
     * @return bool|string
     */
    public function getString()
    {
        if (function_exists('is_' . $this->type)) {

            if ($this->validatesTo === true) {

                return (string)'is_' . $this->type . '(' . $this->operand . ')';

            } else {

                return (string)'!is_' . $this->type . '(' . $this->operand . ')';
            }

        } else {

            return false;
        }
    }

    /**
     * @return bool
     */
    public function invert()
    {
        if ($this->validatesTo === true) {

            $this->validatesTo = false;
            $this->inverted = true;

            return true;

        } elseif ($this->validatesTo === false) {

            $this->validatesTo = true;
            $this->inverted = false;

            return true;

        } else {

            return false;
        }
    }
}
