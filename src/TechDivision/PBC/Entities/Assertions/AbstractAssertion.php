<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 19.06.13
 * Time: 16:20
 * To change this template use File | Settings | File Templates.
 */

namespace TechDivision\PBC\Entities\Assertions;

use TechDivision\PBC\Exceptions\ParserException;
use TechDivision\PBC\Interfaces\Assertion;
use TechDivision\PBC\Utils\PhpLint;

/**
 * Class Assertion
 *
 * This class is used to provide an object base way to pass assertions as e.g. a precondition.
 */
abstract class AbstractAssertion implements Assertion
{
    /**
     * @var boolean
     */
    protected $inverted;

    /**
     *
     */
    public function __construct()
    {
        $this->inverted = false;

        if (!$this->isValid()) {

            throw new ParserException('Could not parse assertion string ' . $this->getString());
        }
    }

    /**
     *
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
     * @return bool
     */
    public function isInverted()
    {
        return $this->inverted;
    }

    /**
     * @return bool
     * @throws \TechDivision\PBC\Exceptions\ParserException
     */
    public function isValid()
    {
        // We need our lint class
        $lint = new PhpLint();

        // Wrap the code as a condition for an if clause
        return $lint->check('if(' . $this->getString() . '){}');
    }
}
