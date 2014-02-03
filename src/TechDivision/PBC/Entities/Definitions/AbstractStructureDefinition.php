<?php
/**
 * File containing the AbstractStructureDefinition class
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

use TechDivision\PBC\StructureMap;
use TechDivision\PBC\Config;
use TechDivision\PBC\Entities\Lists\AssertionList;
use TechDivision\PBC\Entities\Lists\AttributeDefinitionList;
use TechDivision\PBC\Entities\Lists\FunctionDefinitionList;
use TechDivision\PBC\Entities\Lists\TypedListList;
use TechDivision\PBC\Entities\AbstractLockableEntity;
use TechDivision\PBC\Parser\ClassParser;
use TechDivision\PBC\Parser\InterfaceParser;
use TechDivision\PBC\CacheMap;
use TechDivision\PBC\Interfaces\StructureDefinitionInterface;

/**
 * TechDivision\PBC\Entities\Definitions\AbstractStructureDefinition
 *
 * This class acts as a DTO-like (we are not immutable due to protected visibility)
 * entity for describing class definitions
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
abstract class AbstractStructureDefinition extends AbstractLockableEntity implements StructureDefinitionInterface
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
     * @var string $name Name of the class
     */
    protected $name;

    /**
     * @var string $extends Name of the parent class (if any)
     */
    protected $extends;

    /**
     * @var array $constants Class constants
     */
    protected $constants;

    /**
     * @var FunctionDefinitionList $functionDefinitions List of methods this class defines
     */
    protected $functionDefinitions;

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
     * @return bool
     */
    public function hasParents()
    {
        return !empty($this->extends);
    }
}
