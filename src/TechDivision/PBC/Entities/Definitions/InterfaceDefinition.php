<?php
/**
 * File containing the InterfaceDefinition class
 *
 * PHP version 5
 *
 * @category   php-by-contract
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
 * @category   php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Entities
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 *             Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */
class InterfaceDefinition extends AbstractStructureDefinition
{
    /**
     * @var string
     */
    public $path;

    /**
     * @var string
     */
    public $namespace;

    /**
     * @var array
     */
    public $usedNamespaces;

    /**
     * @var string
     */
    public $docBlock;

    /**
     * @var string
     */
    public $name;

    /**
     * @var array
     */
    public $extends;

    /**
     * @var array
     */
    public $constants;

    /**
     * @var AssertionList
     */
    public $invariantConditions;

    /**
     * @var TypedListList
     */
    public $ancestralInvariants;

    /**
     * @var FunctionDefinitionList
     */
    public $functionDefinitions;

    /**
     * @const   string
     */
    const TYPE = 'interface';

    /**
     * Default constructor
     *
     * @param string $docBlock
     * @param string $name
     * @param string $namespace
     * @param array  $extends
     * @param array  $constants
     * @param null   $invariantConditions
     * @param null   $ancestralInvariants
     * @param null   $functionDefinitions
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
     * @return TypedListList
     */
    public function getInvariants()
    {
        $invariants = $this->ancestralInvariants;
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
