<?php
/**
 * File containing the AbstractAssertion class
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

use TechDivision\PBC\Exceptions\ParserException;
use TechDivision\PBC\Interfaces\Assertion;
use TechDivision\PBC\Utils\PhpLint;

/**
 * TechDivision\PBC\Entities\Assertions\AbstractAssertion
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
abstract class AbstractAssertion implements Assertion
{
    /**
     * @var boolean $inverted If the logical meaning was inverted
     */
    protected $inverted;

    /**
     * Default constructor
     */
    public function __construct()
    {
        $this->inverted = false;

        if (!$this->isValid()) {

            throw new ParserException('Could not parse assertion string ' . $this->getString());
        }
    }

    /**
     * Will return a string representing the inverted logical meaning
     *
     * @return string
     */
    public function getInvertString()
    {
        // Invert this instance
        $self = $this;

        $self->invert();

        // Return the string of the inverted instance
        return $self->getString();
    }

    /**
     * Will return true if the assertion is in an inverted state
     *
     * @return bool
     */
    public function isInverted()
    {
        return $this->inverted;
    }

    /**
     * Will test if the assertion will result in a valid PHP statement
     *
     * @return bool
     */
    public function isValid()
    {
        // We need our lint class
        $lint = new PhpLint();

        // Wrap the code as a condition for an if clause
        return $lint->check('if(' . $this->getString() . '){}');
    }
}
