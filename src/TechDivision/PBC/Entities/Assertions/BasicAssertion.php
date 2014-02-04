<?php
/**
 * File containing the BasicAssertion class
 *
 * PHP version 5
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Entities
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 *             Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */

namespace TechDivision\PBC\Entities\Assertions;

/**
 * TechDivision\PBC\Entities\Assertions\BasicAssertion
 *
 * Basic assertions to compare two values
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Entities
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 *             Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */
class BasicAssertion extends AbstractAssertion
{
    /**
     * @var string $firstOperand The first operand to compare
     */
    public $firstOperand;

    /**
     * @var string $secondOperand The second operand to compare
     */
    public $secondOperand;

    /**
     * @var string $operator The operator used for comparison
     */
    public $operator;

    /**
     * @var array $inversionMapping A map to inverse operators
     */
    protected $inversionMapping;

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
