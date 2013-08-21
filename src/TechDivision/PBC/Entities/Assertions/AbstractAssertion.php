<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 19.06.13
 * Time: 16:20
 * To change this template use File | Settings | File Templates.
 */

namespace TechDivision\PBC\Entities\Assertions;

use TechDivision\PBC\Interfaces\Assertion;

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
}