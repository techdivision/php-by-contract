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
class FunctionDefinition
{

    /**
     * @var string $docBlock DocBlock comment of the function
     */
    public $docBlock;

    /**
     * @var boolean $isFinal Is the function final?
     */
    public $isFinal;

    /**
     * @var boolean $isAbstract Is the function abstract?
     */
    public $isAbstract;

    /**
     * @var string $visibility Visibility of the method
     */
    public $visibility;

    /**
     * @var boolean $isStatic Is the method static?
     */
    public $isStatic;

    /**
     * @var string $name Name of the function
     */
    public $name;

    /**
     * @var \TechDivision\PBC\Entities\Lists\ParameterDefinitionList $parameterDefinitions List of parameter definitions
     */
    public $parameterDefinitions;

    /**
     * @var \TechDivision\PBC\Entities\Lists\AssertionList $preconditions Preconditions of this function
     */
    public $preconditions;

    /**
     * @var \TechDivision\PBC\Entities\Lists\TypedListList $ancestralPreconditions Preconditions of any parent functions
     */
    public $ancestralPreconditions;

    /**
     * @var boolean $usesOld Does this function use the pbcOld keyword?
     */
    public $usesOld;

    /**
     * @var string $body Body of the function
     */
    public $body;

    /**
     * @var \TechDivision\PBC\Entities\Lists\AssertionList $postconditions Postconditions of this function
     */
    public $postconditions;

    /**
     * @var \TechDivision\PBC\Entities\Lists\TypedListList $ancestralPostconditions
     *          Postconditions of any parent functions
     */
    public $ancestralPostconditions;

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
     * Will return all preconditions. Native as well as ancestral.
     *
     * @return \TechDivision\PBC\Entities\Lists\TypedListList
     */
    public function getPreconditions()
    {
        $preconditions = $this->ancestralPreconditions;
        $preconditions->add($this->preconditions);

        return $preconditions;
    }

    /**
     * Will return all postconditions. Native as well as ancestral.
     *
     * @return \TechDivision\PBC\Entities\Lists\TypedListList
     */
    public function getPostconditions()
    {
        $postconditions = $this->ancestralPostconditions;
        $postconditions->add($this->postconditions);

        return $postconditions;
    }

    /**
     * Will return the header of this function either in calling or in defining manner.
     * String will stop after the closing ")" bracket, so the string can be used for interfaces as well.
     *
     * @param string $type           Can be either "call" or "definition"
     * @param bool   $markAsOriginal Will mark a method as original by extending it with a suffix
     *
     * @return  string
     */
    public function getHeader($type, $markAsOriginal = false)
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

            // Prepend visibility
            $header .= $this->visibility;

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

        if ($type === 'closure') {

            $header .= 'function()';

        } else {

            // Function name
            $header .= $this->name;

            // Do we need to append the keyword which marks the function as original implementation
            if ($markAsOriginal === true) {

                $header .= PBC_ORIGINAL_FUNCTION_SUFFIX;
            }
        }
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

        if ($type === 'closure' && !empty($parameterString)) {

            $header .= ' use ';

        }

        // Check if we even got something. If not a closure header would be malformed.
        if ($type !== 'closure' || !empty($parameterString)) {
            // Explode to insert commas
            $parameterString = implode(', ', $parameterString);

            // Append the parameters to the header
            $header .= '(' . $parameterString . ')';
        }

        return $header;
    }
}
