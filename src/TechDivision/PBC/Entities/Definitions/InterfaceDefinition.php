<?php
/**
 * File containing the InterfaceDefinition class
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
use TechDivision\PBC\Entities\Lists\AttributeDefinitionList;
use TechDivision\PBC\Entities\Lists\FunctionDefinitionList;
use TechDivision\PBC\Entities\Lists\TypedListList;

/**
 * TechDivision\PBC\Entities\Definitions\InterfaceDefinition
 *
 * This class acts as a DTO-like (we are not immutable due to protected visibility)
 * entity for describing interface definitions
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Entities
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 *             Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 *
 * TODO make us of the lockable entity features
 */
class InterfaceDefinition extends AbstractStructureDefinition
{
    /**
     * @var string $path The path the definition resides in
     */
    public $path;

    /**
     * @var string $namespace The namespace the definition resides in
     */
    public $namespace;

    /**
     * @var array $usedNamespaces Structure names which are references using the use-statement
     * TODO namespaces is not the right terminology
     */
    public $usedNamespaces;

    /**
     * @var string $docBlock DocBlock header of the interface
     */
    public $docBlock;

    /**
     * @var string $name Interface name
     */
    public $name;

    /**
     * @var array $extends The parent interfaces (if any)
     */
    public $extends;

    /**
     * @var array $constants Possible constants the interface defines
     */
    public $constants;

    /**
     * @var AssertionList $invariantConditions Invariant conditions
     * TODO get rid of this as it breaks information hiding
     */
    public $invariantConditions;

    /**
     * @var TypedListList $ancestralInvariants Ancestral invariants
     * TODO get rid of this as it breaks information hiding
     */
    public $ancestralInvariants;

    /**
     * @var FunctionDefinitionList $functionDefinitions List of functions defined within the interface
     */
    public $functionDefinitions;

    /**
     * @const string TYPE The structure type
     */
    const TYPE = 'interface';

    /**
     * Default constructor
     *
     * TODO The constructor does not use all members
     *
     * @param string                      $docBlock            DocBlock header of the interface
     * @param string                      $name                $name Interface name
     * @param string                      $namespace           The namespace the definition resides in
     * @param array                       $extends             The parent interfaces (if any)
     * @param array                       $constants           Possible constants the interface defines
     * @param AssertionList|null          $invariantConditions Invariant conditions
     * @param TypedListList|null          $ancestralInvariants Ancestral invariants
     * @param FunctionDefinitionList|null $functionDefinitions List of functions defined within the interface
     *
     * @return null
     */
    public function __construct(
        $docBlock = '',
        $name = '',
        $namespace = '',
        $extends = array(),
        $constants = array(),
        $invariantConditions = null,
        $ancestralInvariants = null,
        $functionDefinitions = null
    ) {
        $this->docBlock = $docBlock;
        $this->name = $name;
        $this->namespace = $namespace;
        $this->extends = $extends;
        $this->constants = $constants;
        $this->invariantConditions = is_null($invariantConditions) ? new AssertionList() : $invariantConditions;
        $this->ancestralInvariants = is_null($ancestralInvariants) ? new TypedListList() : $ancestralInvariants;
        $this->functionDefinitions = is_null(
            $functionDefinitions
        ) ? new FunctionDefinitionList() : $functionDefinitions;
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
     * Will return all invariants for this interface, direct or indirect
     *
     * @return TypedListList
     * TODO get rid of this
     */
    public function getInvariants()
    {
        $invariants = clone $this->ancestralInvariants;
        $invariants->add($this->invariantConditions);

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
        $result = $this->extends;

        // We got an error that this is nor array, weird but build up a final frontier here
        if (!is_array($result)) {

            $result = array($result);
        }

        return $result;
    }
}
