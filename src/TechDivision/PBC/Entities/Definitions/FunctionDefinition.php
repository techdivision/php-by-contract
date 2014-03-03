<?php
/**
 * File containing the FunctionDefinition class
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

namespace TechDivision\PBC\Entities\Definitions;

use TechDivision\PBC\Entities\Lists\AssertionList;
use TechDivision\PBC\Entities\Lists\TypedListList;
use TechDivision\PBC\Interfaces\AssertionInterface;

/**
 * TechDivision\PBC\Entities\Definitions\FunctionDefinition
 *
 * Provides a definition of a (generally speaking) function.
 * This includes methods as well
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
class FunctionDefinition extends AbstractDefinition
{

    /**
     * @var string $docBlock DocBlock comment of the function
     */
    protected $docBlock;

    /**
     * @var boolean $isFinal Is the function final?
     */
    protected $isFinal;

    /**
     * @var boolean $isAbstract Is the function abstract?
     */
    protected $isAbstract;

    /**
     * @var string $visibility Visibility of the method
     */
    protected $visibility;

    /**
     * @var boolean $isStatic Is the method static?
     */
    protected $isStatic;

    /**
     * @var string $name Name of the function
     */
    protected $name;

    /**
     * @var \TechDivision\PBC\Entities\Lists\ParameterDefinitionList $parameterDefinitions List of parameter definitions
     */
    protected $parameterDefinitions;

    /**
     * @var \TechDivision\PBC\Entities\Lists\AssertionList $preconditions Preconditions of this function
     */
    protected $preconditions;

    /**
     * @var \TechDivision\PBC\Entities\Lists\TypedListList $ancestralPreconditions Preconditions of any parent functions
     */
    protected $ancestralPreconditions;

    /**
     * @var boolean $usesOld Does this function use the pbcOld keyword?
     */
    protected $usesOld;

    /**
     * @var string $body Body of the function
     */
    protected $body;

    /**
     * @var \TechDivision\PBC\Entities\Lists\AssertionList $postconditions Postconditions of this function
     */
    protected $postconditions;

    /**
     * @var \TechDivision\PBC\Entities\Lists\TypedListList $ancestralPostconditions
     *          Postconditions of any parent functions
     */
    protected $ancestralPostconditions;

    /**
     * Default constructor
     */
    public function __construct()
    {
        $this->docBlock = '';
        $this->isFinal = false;
        $this->isAbstract = false;
        $this->visibility = '';
        $this->isStatic = false;
        $this->name = '';
        $this->parameterDefinitions = array();
        $this->preconditions = new AssertionList();
        $this->ancestralPreconditions = new TypedListList();
        $this->usesOld = false;
        $this->body = '';
        $this->postconditions = new AssertionList();
        $this->ancestralPostconditions = new TypedListList();
    }

    /**
     * Getter method for attribute $docBlock
     *
     * @return string
     */
    public function getDocBlock()
    {
        return $this->docBlock;
    }

    /**
     * Getter method for attribute $isFinal
     *
     * @return boolean
     */
    public function getIsFinal()
    {
        return $this->isFinal;
    }

    /**
     * Getter method for attribute $isAbstract
     *
     * @return boolean
     */
    public function getIsAbstract()
    {
        return $this->isAbstract;
    }

    /**
     * Getter method for attribute $visibility
     *
     * @return string
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * Getter method for attribute $isStatic
     *
     * @return boolean
     */
    public function getIsStatic()
    {
        return $this->isStatic;
    }

    /**
     * Getter method for attribute $name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Getter method for attribute $parameterDefinitions
     *
     * @return ParameterDefinitionList
     */
    public function getParameterDefinitions()
    {
        return $this->parameterDefinitions;
    }

    /**
     * Getter method for attribute $preconditions
     *
     * @return AssertionList
     */
    public function getPreconditions()
    {
        return $this->preconditions;
    }

    /**
     * Getter method for attribute $ancestralPreconditions
     *
     * @return null|TypedListList
     */
    public function getAncestralPreconditions()
    {
        return $this->ancestralPreconditions;
    }

    /**
     * Getter method for attribute $usesOld
     *
     * @return boolean
     */
    public function getUsesOld()
    {
        return $this->usesOld;
    }

    /**
     * Getter method for attribute $body
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Getter method for attribute $postconditions
     *
     * @return AssertionList
     */
    public function getPostconditions()
    {
        return $this->postconditions;
    }

    /**
     * Getter method for attribute $ancestralPostconditions
     *
     * @return null|TypedListList
     */
    public function getAncestralPostconditions()
    {
        return $this->ancestralPreconditions;
    }

    /**
     * Will return all preconditions. Direct as well as ancestral.
     *
     * @param boolean $nonPrivateOnly   Make this true if you only want conditions which do not have a private context
     * @param boolean $filterMismatches Do we have to filter condition mismatches due to signature changes
     *
     * @return \TechDivision\PBC\Entities\Lists\TypedListList
     */
    public function getAllPreconditions($nonPrivateOnly = false, $filterMismatches = true)
    {
        $preconditions = clone $this->ancestralPreconditions;
        $preconditions->add($this->preconditions);

        // If we need to we will filter all the non private conditions from the lists
        // Preconditions have to be flattened as the signature of a function (and therefore it's parameter list)
        // might change within a structure hierarchy.
        // We have to do that here, as we cannot risk to delete conditions which use non existing parameters, as
        // a potential child method might want to inherit grandparental conditions which do not make sense for us
        // (but do for them).
        if ($nonPrivateOnly === true || $filterMismatches === true) {

            $preconditionListIterator = $preconditions->getIterator();
            foreach ($preconditionListIterator as $preconditionList) {

                $preconditionIterator = $preconditionList->getIterator();
                foreach ($preconditionIterator as $key => $precondition) {

                    // The privacy issue
                    if ($nonPrivateOnly === true && $precondition->isPrivateContext()) {

                        $preconditionList->delete($key);
                    }

                    // The mismatch filter
                    if ($filterMismatches === true && $this->conditionIsMismatch($precondition)) {

                        $preconditionList->delete($key);
                    }
                }
            }
        }

        // Return what is left
        return $preconditions;
    }

    /**
     * Will return all postconditions. Direct as well as ancestral.
     *
     * @param boolean $nonPrivateOnly Make this true if you only want conditions which do not have a private context
     *
     * @return \TechDivision\PBC\Entities\Lists\TypedListList
     */
    public function getAllPostconditions($nonPrivateOnly = false)
    {
        $postconditions = clone $this->ancestralPostconditions;
        $postconditions->add($this->postconditions);

        // If we need to we will filter all the non private conditions from the lists
        if ($nonPrivateOnly === true) {

            $postconditionListIterator = $postconditions->getIterator();
            foreach ($postconditionListIterator as $postconditionList) {

                $postconditionIterator = $postconditionList->getIterator();
                foreach ($postconditionIterator as $key => $postcondition) {

                    if ($postcondition->isPrivateContext()) {

                        $postconditionList->delete($key);
                    }
                }
            }
        }

        // Return what is left
        return $postconditions;
    }

    /**
     * Will return the header of this function either in calling or in defining manner.
     * String will stop after the closing ")" bracket, so the string can be used for interfaces as well.
     *
     * @param string $type   Can be either "call" or "definition"
     * @param string $suffix Suffix for the function name
     * @param bool   $hideMe Will mark a method as original by extending it with a suffix
     *
     * @return  string
     */
    public function getHeader($type, $suffix = '', $hideMe = false)
    {
        $header = '';

        // We have to do some more work if we need the definition header
        if ($type === 'definition') {

            // Check for final or abstract (abstract cannot be used if final)
            if ($this->isFinal) {

                $header .= ' final ';
            } elseif ($this->isAbstract) {

                $header .= ' abstract ';
            }

            // Do we need to hide this function? If so we will make it protected
            if ($hideMe === false) {

                // Prepend visibility
                $header .= $this->visibility;

            } else {

                $header .= 'protected';
            }

            // Are we static?
            if ($this->isStatic) {

                $header .= ' static ';
            }

            // Function keyword and name
            $header .= ' function ';
        }

        // If we have to generate code for a call we have to check for either static or normal access
        if ($type === 'call') {
            if ($this->isStatic === true) {

                $header .= 'self::';
            } else {

                $header .= '$this->';
            }
        }

        // Function name + the suffix we might have gotten
        $header .= $this->name . $suffix;

        // Iterate over all parameters and create the parameter string.
        // We will create the string we need, either for calling the function or for defining it.
        $parameterString = array();
        $parameterIterator = $this->parameterDefinitions->getIterator();
        for ($k = 0; $k < $parameterIterator->count(); $k++) {

            // Our parameter
            $parameter = $parameterIterator->current();

            // Fill our strings
            $parameterString[] = $parameter->getString($type);

            // Next assertion please
            $parameterIterator->next();
        }

        // Check if we even got something. If not a closure header would be malformed.
        if (!empty($parameterString)) {
            // Explode to insert commas
            $parameterString = implode(', ', $parameterString);

            // Append the parameters to the header
            $header .= '(' . $parameterString . ')';

        } else {

            $header .= '()';
        }

        return $header;
    }

    /**
     * This method will check if a certain assertion mismatches the scope of this function.
     *
     * @param \TechDivision\PBC\Interfaces\AssertionInterface $assertion The assertion to check for a possible mismatch
     *          within this function context
     *
     * @return boolean
     */
    protected function conditionIsMismatch(AssertionInterface $assertion)
    {
        // If the minimal scope is above or below function scope we cannot determine if this is a mismatch in
        // function scope.
        if ($assertion->getMinScope() !== 'function') {

            return false;
        }

        // We will get all parameters and check if we can find any of it in the assertion string.
        // If not then we have a mismatch as the condition is only function scoped
        $assertionString = $assertion->getString();
        $parameterIterator = $this->parameterDefinitions->getIterator();
        foreach ($parameterIterator as $parameter) {

            if (strpos($assertionString, $parameter->name) !== false) {

                return false;
            }
        }

        // Still here, that does not sound good
        return true;
    }
}
