<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 19.06.13
 * Time: 16:20
 * To change this template use File | Settings | File Templates.
 */

namespace TechDivision\PBC\Entities\Assertions;

use TechDivision\PBC\Entities\Lists\AssertionList;

/**
 * Class TypeAssertion
 *
 * This class is used to provide an object base way to pass assertions as e.g. a precondition.
 */
class ChainedAssertion extends AbstractAssertion
{
    /**
     * @var AssertionList
     */
    public $assertionList;

    /**
     * @var array
     */
    public $combinators;

    /**
     * @var boolean
     */
    public $validatesTo;

    /**
     * @var array
     */
    private $inversionMapping;

    /**
     * @param $_assertionList
     * @param $_combinators
     */
    public function __construct(AssertionList $_assertionList, $_combinators)
    {
        $this->assertionList = $_assertionList;
        $this->combinators = $_combinators;

        // Set the mapping for our inversion
        $this->inversionMapping = array(
            'and' => 'or',
            '&&' => '||',
            'or' => 'and',
            '||' => '&&'
        );

        // There must be enough combinators to chain up the assertions.
        // If not, we do not stand a chance to make this work.
        if ($_assertionList->count() !== (count($_combinators) + 1)) {

            throw new \InvalidArgumentException();
        }
    }

    /**
     * @return bool|string
     */
    public function getString()
    {
        // Simply iterate over all assertions and chain their string representation together.
        $string = '';
        $iterator = $this->assertionList->getIterator();
        for ($i = 0; $i < $iterator->count(); $i++) {

            // Get the string representation of this assertion
            $string .= $iterator->current()->getString();

            // Follow it up with a combinator
            if (isset($this->combinators[$i])) {

                $string .= ' ' . $this->combinators[$i] . ' ';
            }

            // Move the iterator
            $iterator->next();
        }

        return $string;
    }

    /**
     * @return bool
     */
    public function invert()
    {
        if ($this->inverted === true) {

            $this->inverted = false;

        }  else {

            $this->inverted = true;
        }

        // Iterate over all assertions and invert them.
        $iterator = $this->assertionList->getIterator();
        for ($i = 0; $i < $iterator->count(); $i++) {

            // Get the string representation of this assertion
            $iterator->current()->invert();

            // Move the iterator
            $iterator->next();
        }

        // Now invert all combinators.
        foreach($this->combinators as $key => $combinator) {

            if (isset($this->inversionMapping[$combinator])) {

                $this->combinators[$key] = $this->inversionMapping[$combinator];
            }
        }
    }
}