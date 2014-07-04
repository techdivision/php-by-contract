<?php
/**
 * File containing the TypeAssertion class
 *
 * PHP version 5
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Entities
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */

namespace TechDivision\PBC\Entities\Assertions;

/**
 * TechDivision\PBC\Entities\Assertions\TypeAssertion
 *
 * This class will enable us to check for basic types
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Entities
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */
class TypeAssertion extends AbstractAssertion
{
    /**
     * @var string $operand The operand we have to check
     */
    public $operand;

    /**
     * @var string $type The type we have to check for
     */
    public $type;

    /**
     * @var bool $validatesTo The bool value we should test against
     */
    public $validatesTo;

    /**
     * Default constructor
     *
     * @param string $operand The operand we have to check
     * @param string $type    The type we have to check for
     */
    public function __construct($operand, $type)
    {
        $this->operand = $operand;
        $this->validatesTo = true;
        $this->type = $type;

        parent::__construct();
    }

    /**
     * Will return a string representation of this assertion. Will return false if the type is unknown.
     *
     * @return boolean|string
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
     * Invert the logical meaning of this assertion
     *
     * @return boolean
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
