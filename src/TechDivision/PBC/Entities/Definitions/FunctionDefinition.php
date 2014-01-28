<?php
/**
 * TechDivision\PBC\Entities\Definitions\FunctionDefinition
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 */
namespace TechDivision\PBC\Entities\Definitions;

use TechDivision\PBC\Entities\Lists\AssertionList;
use TechDivision\PBC\Entities\Lists\TypedListList;

/**
 * @package     TechDivision\PBC
 * @subpackage  Entities
 * @copyright   Copyright (c) 2013 <info@techdivision.com> - TechDivision GmbH
 * @license     http://opensource.org/licenses/osl-3.0.php
 *              Open Software License (OSL 3.0)
 * @author      Bernhard Wick <b.wick@techdivision.com>
 */
class FunctionDefinition
{

    /**
     * @var string
     */
    public $docBlock;

    /**
     * @var boolean
     */
    public $isFinal;

    /**
     * @var boolean
     */
    public $isAbstract;

    /**
     * @var string
     */
    public $visibility;

    /**
     * @var boolean
     */
    public $isStatic;

    /**
     * @var string
     */
    public $name;

    /**
     * @var ParameterDefinitionList
     */
    public $parameterDefinitions;

    /**
     * @var AssertionList
     */
    public $preconditions;

    /**
     * @var TypedListList
     */
    public $ancestralPreconditions;

    /**
     * @var boolean
     */
    public $usesOld;

    /**
     * @var string
     */
    public $body;

    /**
     * @var AssertionList
     */
    public $postconditions;

    /**
     * @var TypedListList
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
     * @return TypedListList
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
     * @return TypedListList
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
     * @param   string $type Can be either "call" or "definition"
     * @param   bool   $markAsOriginal
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
