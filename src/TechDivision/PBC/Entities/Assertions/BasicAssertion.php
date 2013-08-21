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
 * Class BasicAssertion
 *
 * This class is used to provide an object base way to pass assertions as e.g. a precondition.
 */
class BasicAssertion extends AbstractAssertion
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
     * @var array
     */
    private $inversionMapping;

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

        // Set the mapping for our inversion
        $this->inversionMapping = array(
            '==' => '!=',
            '===' => '!==',
            '<>' => '==',
            '<' => '>=',
            '>' => '<=',
            '<=' => '>',
            '>=' => '<',
            '!=' => '==',
            '!==' => '==='
        );
    }

    /**
     * @return string
     */
    public function getString()
    {
        return (string) $this->firstOperand . ' ' . $this->operator . ' ' . $this->secondOperand;
    }

    /**
     * @return string
     */
    public function getInvertString()
    {
        if (isset($this->inversionMapping[$this->operator])) {

            return (string) $this->firstOperand . ' ' . $this->inversionMapping[$this->operator] . ' ' . $this->secondOperand;
        }
    }

    /**
     * @return bool
     */
    public function invert()
    {
        if (isset($this->inversionMapping[$this->operator])) {

            // Invert the operator
            $this->operator = $this->inversionMapping[$this->operator];
            // Don't forget to mark this assertion as inverted
            if ($this->inverted === true) {

                $this->inverted = false;

            } else {

                $this->inverted = true;
            }

            return true;

        } else {

            return false;
        }
    }
}