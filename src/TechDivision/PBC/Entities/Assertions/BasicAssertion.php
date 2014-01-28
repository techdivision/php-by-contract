<?php
/**
 * TechDivision\PBC\Entities\Assertions\BasicAssertion
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 */
namespace TechDivision\PBC\Entities\Assertions;

/**
 * This class is used to provide an object base way to pass assertions as e.g. a precondition.
 *
 * @package     TechDivision\PBC
 * @subpackage  Entities
 * @copyright   Copyright (c) 2013 <info@techdivision.com> - TechDivision GmbH
 * @license     http://opensource.org/licenses/osl-3.0.php
 *              Open Software License (OSL 3.0)
 * @author      Bernhard Wick <b.wick@techdivision.com>
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
        return (string)$this->firstOperand . ' ' . $this->operator . ' ' . $this->secondOperand;
    }

    /**
     * @return string
     */
    public function getInvertString()
    {
        if (isset($this->inversionMapping[$this->operator])) {

            return (string)$this->firstOperand . ' ' .
            $this->inversionMapping[$this->operator] . ' ' . $this->secondOperand;
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
