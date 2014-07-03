<?php
/**
 * File containing the ClassDefinition class
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

namespace TechDivision\PBC\Entities\Definitions;

use TechDivision\PBC\Entities\Lists\AssertionList;
use TechDivision\PBC\Entities\Lists\AttributeDefinitionList;
use TechDivision\PBC\Entities\Lists\FunctionDefinitionList;
use TechDivision\PBC\Entities\Lists\TypedListList;

/**
 * TechDivision\PBC\Entities\Definitions\ClassDefinition
 *
 * This class acts as a DTO-like (we are not immutable due to protected visibility)
 * entity for describing class definitions
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Entities
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */
class ClassDefinition extends AbstractStructureDefinition
{
    /**
     * @var string $path File path to the class definition
     */
    protected $path;

    /**
     * @var string $namespace The namespace the class belongs to
     */
    protected $namespace;

    /**
     * @var array $usedNamespaces All classes which are referenced by the "use" keyword
     */
    protected $usedNamespaces;

    /**
     * @var string $docBlock The initial class docblock header
     */
    protected $docBlock;

    /**
     * @var boolean $isFinal Is this a final class
     */
    protected $isFinal;

    /**
     * @var boolean $isAbstract Is this class abstract
     */
    protected $isAbstract;

    /**
     * @var string $name Name of the class
     */
    protected $name;

    /**
     * @var string $extends Name of the parent class (if any)
     */
    protected $extends;

    /**
     * @var array $implements Array of interface names this class implements
     */
    protected $implements;

    /**
     * @var array $constants Class constants
     */
    protected $constants;

    /**
     * @var AttributeDefinitionList $attributeDefinitions List of defined attributes
     */
    protected $attributeDefinitions;

    /**
     * @var AssertionList $invariantConditions List of directly defined invariant conditions
     */
    protected $invariantConditions;

    /**
     * @var TypedListList $ancestralInvariants List of lists of any ancestral invariants
     */
    protected $ancestralInvariants;

    /**
     * @var FunctionDefinitionList $functionDefinitions List of methods this class defines
     */
    protected $functionDefinitions;

    /**
     * @const string TYPE The structure type
     */
    const TYPE = 'class';

    /**
     * Default constructor
     *
     * @param string $path                 File path to the class definition
     * @param string $namespace            The namespace the class belongs to
     * @param array  $usedNamespaces       All classes which are referenced by the "use" keyword
     * @param string $docBlock             The initial class docblock header
     * @param bool   $isFinal              Is this a final class
     * @param bool   $isAbstract           Is this class abstract
     * @param string $name                 Name of the class
     * @param string $extends              Name of the parent class (if any)
     * @param array  $implements           Array of interface names this class implements
     * @param array  $constants            Class constants
     * @param null   $attributeDefinitions List of defined attributes
     * @param null   $invariantConditions  List of directly defined invariant conditions
     * @param null   $ancestralInvariants  List of lists of any ancestral invariants
     * @param null   $functionDefinitions  List of methods this class defines
     */
    public function __construct(
        $path = '',
        $namespace = '',
        $usedNamespaces = array(),
        $docBlock = '',
        $isFinal = false,
        $isAbstract = false,
        $name = '',
        $extends = '',
        $implements = array(),
        $constants = array(),
        $attributeDefinitions = null,
        $invariantConditions = null,
        $ancestralInvariants = null,
        $functionDefinitions = null
    ) {
        $this->path = $path;
        $this->namespace = $namespace;
        $this->usedNamespaces = $usedNamespaces;
        $this->docBlock = $docBlock;
        $this->isFinal = $isFinal;
        $this->isAbstract = $isAbstract;
        $this->name = $name;
        $this->extends = $extends;
        $this->implements = $implements;
        $this->constants = $constants;
        $this->attributeDefinitions = is_null(
            $attributeDefinitions
        ) ? new AttributeDefinitionList() : $attributeDefinitions;
        $this->invariantConditions = is_null($invariantConditions) ? new AssertionList() : $invariantConditions;
        $this->ancestralInvariants = is_null($ancestralInvariants) ? new TypedListList() : $ancestralInvariants;
        $this->functionDefinitions = is_null(
            $functionDefinitions
        ) ? new FunctionDefinitionList() : $functionDefinitions;
    }

    /**
     * Getter method for attribute $ancestralInvariants
     *
     * @return null|TypedListList
     */
    public function getAncestralInvariants()
    {
        return $this->ancestralInvariants;
    }

    /**
     * Getter method for attribute $attributeDefinitions
     *
     * @return null|AttributeDefinitionList
     */
    public function getAttributeDefinitions()
    {
        return $this->attributeDefinitions;
    }

    /**
     * Getter method for attribute $constants
     *
     * @return array
     */
    public function getConstants()
    {
        return $this->constants;
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
     * Getter method for attribute $extends
     *
     * @return string
     */
    public function getExtends()
    {
        return $this->extends;
    }

    /**
     * Getter method for attribute $functionDefinitions
     *
     * @return null|FunctionDefinitionList
     */
    public function getFunctionDefinitions()
    {
        return $this->functionDefinitions;
    }

    /**
     * Getter method for attribute $implements
     *
     * @return array
     */
    public function getImplements()
    {
        return $this->implements;
    }

    /**
     * Getter method for attribute $invariantConditions
     *
     * @return null|AssertionList
     */
    public function getInvariantConditions()
    {
        return $this->invariantConditions;
    }

    /**
     * Getter method for attribute $isAbstract
     *
     * @return bool
     */
    public function getIsAbstract()
    {
        return $this->isAbstract;
    }

    /**
     * Getter method for attribute $isFinal
     *
     * @return bool
     */
    public function getIsFinal()
    {
        return $this->isFinal;
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
     * Getter method for attribute $namespace
     *
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * Getter method for attribute $path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Getter method for attribute $usedNamespace
     *
     * @return array
     */
    public function getUsedNamespaces()
    {
        return $this->usedNamespaces;
    }

    /**
     * Will return the qualified name of a structure
     *
     * @return string
     */
    public function getQualifiedName()
    {
        if (empty($this->namespace)) {

            return $this->name;

        } else {

            return $this->namespace . '\\' . $this->name;
        }
    }

    /**
     * Will return the type of the definition.
     *
     * @return string
     */
    public function getType()
    {
        return self::TYPE;
    }

    /**
     * Does this class have a parent class?
     *
     * @return bool
     */
    public function hasParents()
    {
        return !empty($this->extends);
    }

    /**
     * Will return all invariants. direct and introduced (by ancestral structures) alike.
     *
     * @param boolean $nonPrivateOnly Make this true if you only want conditions which do not have a private context
     *
     * @return \TechDivision\PBC\Entities\Lists\TypedListList
     */
    public function getInvariants($nonPrivateOnly = false)
    {
        // We have to clone it here, otherwise we might have weird side effects, of having the "add()" operation
        // persistent on $this->ancestralInvariants
        $invariants = clone $this->ancestralInvariants;
        $invariants->add($this->invariantConditions);

        // If we need to we will filter all the non private conditions from the lists
        if ($nonPrivateOnly === true) {

            $invariantListIterator = $invariants->getIterator();
            foreach ($invariantListIterator as $invariantList) {

                $invariantIterator = $invariantList->getIterator();
                foreach ($invariantIterator as $key => $invariant) {

                    if ($invariant->isPrivateContext()) {

                        $invariantList->delete($key);
                    }
                }
            }
        }

        // Return what is left
        return $invariants;
    }

    /**
     * Will return a list of all dependencies eg. parent class, interfaces and traits.
     *
     * @return array
     */
    public function getDependencies()
    {
        // Get our interfaces
        $result = $this->implements;

        // We got an error that this is nor array, weird but build up a final frontier here
        if (!is_array($result)) {

            $result = array($result);
        }

        // Add our parent class (if any)
        if (!empty($this->extends)) {

            $result[] = $this->extends;
        }

        return $result;
    }

    /**
     * Will flatten all conditions available at the time of the call.
     * That means this method will check which conditions make sense in an inheritance context and will drop the
     * others.
     * This method MUST be protected/private so it will run through \TechDivision\PBC\Entities\AbstractLockableEntity's
     * __call() method which will check the lock status before doing anything.
     *
     * @return bool
     */
    protected function flattenConditions()
    {
        // As our lists only supports unique entries anyway, the only thing left is to check if the condition's
        // assertions can be fulfilled (would be possible as direct assertions), and flatten the contained
        // function definitions as well
        $ancestralConditionIterator = $this->ancestralInvariants->getIterator();
        foreach ($ancestralConditionIterator as $conditionList) {

            $conditionListIterator = $conditionList->getIterator();
            foreach ($conditionListIterator as $assertion) {


            }
        }

        // No flatten all the function definitions we got
        $functionDefinitionIterator = $this->functionDefinitions->getIterator();
        foreach ($functionDefinitionIterator as $functionDefinition) {

            $functionDefinition->flattenConditions();
        }

        return false;
    }
}
