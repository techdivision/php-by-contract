<?php
/**
 * File containing the InstanceAssertion class
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
 * TechDivision\PBC\Entities\Assertions\InstanceAssertion
 *
 * This class is used to provide an object base way to pass assertions as e.g. a precondition.
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
class InstanceAssertion extends AbstractAssertion
{
    /**
     * @var string $operand The operand we have to check
     */
    public $operand;

    /**
     * @var string $class The name of the class we have to check for
     */
    public $class;

    /**
     * Default constructor
     *
     * @param string $operand The operand we have to check
     * @param string $class   The name of the class we have to check for
     */
    public function __construct($operand, $class)
    {
        $this->operand = $operand;
        $this->class = $class;

        parent::__construct();
    }

    /**
     * Will return a string representation of this assertion
     *
     * @return string
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
     * Invert the logical meaning of this assertion
     *
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
