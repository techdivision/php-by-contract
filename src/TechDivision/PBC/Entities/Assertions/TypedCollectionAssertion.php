<?php
/**
 * File containing the TypedCollectionAssertion class
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
 * TechDivision\PBC\Entities\Assertions\TypedCollectionAssertion
 *
 * Provides the option to check "collections" of the form array<Type>
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Entities
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */
class TypedCollectionAssertion extends AbstractAssertion
{
    /**
     * @var string $operand The operand to check
     */
    public $operand;

    /**
     * @var string $type The type are are checking against
     */
    public $type;

    /**
     * @var string $comparator Comparator, === by default
     */
    protected $comparator;

    /**
     * Default constructor
     *
     * @param string $operand The operand to check
     * @param string $type    The type are are checking against
     */
    public function __construct($operand, $type)
    {
        $this->operand = $operand;
        $this->type = $type;
        $this->comparator = '===';

        parent::__construct();
    }

    /**
     * Will return a string representation of this assertion
     *
     * @return string
     */
    public function getString()
    {
        $code = 'count(array_filter(' . $this->operand . ', function(&$value) {
        if (!$value instanceof ' . $this->type . ') {

            return true;
        }
        })) ' . $this->comparator . ' 0';

        return $code;
    }

    /**
     * Invert the logical meaning of this assertion
     *
     * @return bool
     */
    public function invert()
    {
        if ($this->inverted === false) {

            $this->comparator = '!==';
            $this->inverted = true;

            return true;

        } elseif ($this->inverted === true) {

            $this->comparator = '===';
            $this->inverted = false;

            return true;

        } else {

            return false;
        }
    }
}
