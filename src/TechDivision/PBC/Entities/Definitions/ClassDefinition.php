<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 20.06.13
 * Time: 10:31
 * To change this template use File | Settings | File Templates.
 */
namespace TechDivision\PBC\Entities\Definitions;

use TechDivision\PBC\StructureMap;
use TechDivision\PBC\Config;
use TechDivision\PBC\Entities\Lists\AssertionList;
use TechDivision\PBC\Entities\Lists\AttributeDefinitionList;
use TechDivision\PBC\Entities\Lists\FunctionDefinitionList;
use TechDivision\PBC\Entities\Lists\TypedListList;
use TechDivision\PBC\Parser\ClassParser;
use TechDivision\PBC\Parser\InterfaceParser;
use TechDivision\PBC\Proxies\Cache;
use TechDivision\PBC\Interfaces\StructureDefinitionInterface;

/**
 * Class ClassDefinition
 */
class ClassDefinition implements StructureDefinitionInterface
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
    public $name;

    /**
     * @var string
     */
    public $extends;

    /**
     * @var array
     */
    public $implements;

    /**
     * @var array
     */
    public $constants;

    /**
     * @var AttributeDefinitionList
     */
    public $attributeDefinitions;

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
    const TYPE = 'class';

    /**
     * Default constructor
     */
    public function __construct(
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
     * @return bool
     */
    public function hasParents()
    {
        return !empty($this->extends);
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
        $result = $this->implements;

        // We got an error that this is nor array, weird but build up a final frontier here
        if (!is_array($result)) {

            $result = array($result);
        }

        // Add our parent class (if any)
        if ($this->extends !== '') {

            $result[] = $this->extends;
        }

        return $result;
    }

    /**
     * @param $ancestorDefinitions
     * @return bool
     */
    protected function getAncestralConditions($ancestorDefinitions)
    {
        // Maybe we do not have to do anything
        if (count($ancestorDefinitions) === 0) {

            return false;
        }

        // We have to get a map of all the methods we have to know which got overridden
        if ($this->functionDefinitions->count() === 0) {

            return false;

        } else {

            foreach ($ancestorDefinitions as $ancestorDefinition) {

                $functionIterator = $ancestorDefinition->functionDefinitions->getIterator();
                for ($j = 0; $j < $functionIterator->count(); $j++) {

                    // Do we have a method like that?
                    $function = $this->functionDefinitions->get($functionIterator->current()->name);

                    if ($function !== false) {

                        // Get the pre- and postconditions of the ancestor
                        if ($functionIterator->current()->preconditions->count() > 0) {

                            $function->ancestralPreconditions->add($functionIterator->current()->preconditions);
                        }
                        if ($functionIterator->current()->postconditions->count() > 0) {

                            $function->ancestralPostconditions->add($functionIterator->current()->postconditions);
                        }

                        // Check if we have to use the old keyword now
                        if ($functionIterator->current()->usesOld === true) {

                            $function->usesOld = true;
                        }

                        // Safe the enhanced functionDefinition back
                        $this->functionDefinitions->set($function->name, $function);
                    }

                    // increment iterator
                    $functionIterator->next();
                }
            }
        }

        // We are still here, seems good
        return true;
    }

    /**
     *
     */
    protected function getAncestralInvariant($ancestorDefinitions)
    {
        // We have to get all the contracts for our interfaces and parent class
        if ($this->extends !== '') {

            // Get the definition of our parent
            $classParser = new ClassParser();
            $cache = Cache::getInstance();
            $files = $cache->getFiles();

            if (isset($files[$this->extends])) {

                $parent = $classParser->getDefinition($files[$this->extends]['path'], $this->extends);

                // Make the parent get their parent's invariant contracts
                $isChild = $parent->getAncestralInvariant($ancestorDefinitions);

                // Add them to this invariant list
                $this->ancestralInvariants->add($parent->invariantConditions);

                // If our parent is a child as well we need their invariants too
                if ($isChild === true) {

                    // Add them to this invariant list
                    $iterator = $parent->ancestralInvariants->getIterator();
                    for ($i = 0; $i < $iterator->count(); $i++) {

                        $this->ancestralInvariants->add($iterator->current());

                        // Set the iterator to the next iteration
                        $iterator->next();
                    }
                }

                return true;
            }
        }

        return false;
    }
}