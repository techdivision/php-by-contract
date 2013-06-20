<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 19.06.13
 * Time: 16:20
 * To change this template use File | Settings | File Templates.
 */

namespace TechDivision\PBC\Entities;

/**
 * Class Assertion
 *
 * This class is used to provide an object base way to pass assertions as e.g. a precondition.
 */
class Assertion
{
    /**
     * @var
     */
    public $firstOperand;

    /**
     * @var
     */
    public $secondOperand;

    /**
     * @var
     */
    public $operator;

    /**
     * @param $_firstOperand
     * @param $_secondOperand
     * @param $_operator
     */
    public function __construct($_firstOperand = '', $_secondOperand = '', $_operator = '')
    {
        $this->firstOperand = $_firstOperand;
        $this->secondOperand = $_secondOperand;
        $this->operator = $_operator;
    }
}