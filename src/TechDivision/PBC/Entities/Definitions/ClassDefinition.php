<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 20.06.13
 * Time: 10:31
 * To change this template use File | Settings | File Templates.
 */
namespace TechDivision\PBC\Entities\Definitions;

use TechDivision\PBC\Entities\Lists\AssertionList;
use TechDivision\PBC\Entities\Lists\AttributeDefinitionList;
use TechDivision\PBC\Entities\Lists\FunctionDefinitionList;
use TechDivision\PBC\Entities\Lists\TypedListList;
use TechDivision\PBC\Parser\ClassParser;
use TechDivision\PBC\Parser\InterfaceParser;
use TechDivision\PBC\Proxies\Cache;
use TechDivision\PBC\Interfaces\StructureDefinition;

/**
 * Class ClassDefinition
 */
class ClassDefinition implements StructureDefinition
{
    /**
     * @var string
     */
    public $namespace;

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
     * Default constructor
     */
    public function __construct()
    {
        $this->docBlock = '';
        $this->isFinal = false;
        $this->isAbstract = false;
        $this->name = '';
        $this->extends = '';
        $this->implements = array();
        $this->constants = array();
        $this->attributeDefinitions = new AttributeDefinitionList();
        $this->invariantConditions = new AssertionList();
        $this->ancestralInvariants = new TypedListList();
        $this->functionDefinitions = new FunctionDefinitionList();
    }

    /**
     * Will return a list of all dependencies eg. parent class, interfaces and traits.
     *
     * @return array
     */
    public function getDependencies()
    {
        $result = $this->implements;
        $result[] = $this->extends;

        return $result;
    }

    /**
     * Finalize this class definition
     *
     * Will make the final steps to complete the class definition.
     * Mostly this consists of getting the ancestral invariants and
     * method pre- and postconditions.
     *
     * @return  boolean
     */
    public function finalize()
    {
        // We have to get all ancestral classes and interfaces
        $ancestor = array();
        if (!empty($this->implements)) {

            $ancestors['interface'] = $this->implements;
        }
        if (!empty($this->extends)) {

            $ancestors['class'][] = $this->extends;
        }

        // Is there anything left
        if (empty($ancestors)) {

            return true;
        }

        // Now finalize them recursively using the needed parsers
        $parsers = array('interface' => new InterfaceParser(), 'class' => new ClassParser());
        $cache = Cache::getInstance();
        $files = $cache->getFiles();
        $ancestorDefinitions = array();
        foreach ($ancestors as $key => $ancestorList) {

            // If we don't have a parser for this data we can skip that turn
            if (!isset($parsers[$key])) {

                continue;

            } else {

                $parser = $parsers[$key];
            }

            foreach ($ancestorList as $ancestor) {
                // Do we have this pestering leading \?
                if (strpos($ancestor, '\\') === 0) {

                    $ancestor = ltrim($ancestor, '\\');
                }

                // Do we know this file?
                if (isset($files[$ancestor])) {

                    $ancestorDefinitions[$key] = $parser->getDefinitionFromFile($files[$ancestor]['path'], $ancestor);
                    $ancestorDefinitions[$key]->finalize();

                } elseif (isset($files[$this->namespace . '\\' . $ancestor])) {

                    $ancestorDefinitions[$key] = $parser->getDefinitionFromFile($files[$this->namespace . '\\' . $ancestor]['path'],
                        $ancestor);
                    $ancestorDefinitions[$key]->finalize();

                }
            }
        }

        // Get all the ancestral method pre- and postconditions
        $this->getAncestralConditions($ancestorDefinitions);

        return true;
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
        $methods = array();
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
                        if ($functionIterator->current()->preConditions->count() > 0) {

                            $function->ancestralPreConditions->add($functionIterator->current()->preConditions);
                        }
                        if ($functionIterator->current()->postConditions->count() > 0) {

                            $function->ancestralPostConditions->add($functionIterator->current()->postConditions);
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

                $parent = $classParser->getDefinitionFromFile($files[$this->extends]['path'], $this->extends);

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