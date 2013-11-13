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
use TechDivision\PBC\Interfaces\StructureDefinition;
use TechDivision\PBC\Parser\InterfaceParser;
use TechDivision\PBC\Proxies\Cache;

/**
 * Class InterfaceDefinition
 */
class InterfaceDefinition implements StructureDefinition
{
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
     * Default constructor
     */
    public function __construct()
    {
        $this->docBlock = '';
        $this->name = '';
        $this->extends = '';
        $this->constants = array();
        $this->invariantConditions = new AssertionList();
        $this->ancestralInvariants = new TypedListList();
        $this->functionDefinitions = new FunctionDefinitionList();
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
     * @return bool
     */
    public function hasParents()
    {
        return !empty($this->extends);
    }

    /**
     * Will return a list of all dependencies eg. parent class, interfaces and traits.
     *
     * @return array
     */
    public function getDependencies()
    {
        return $this->extends;
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
        // We have to get all ancestral interfaces
        $ancestors = $this->extends;

        // Do we even have something like that?
        if (empty($ancestors)) {

            return true;
        }

        // Now finalize them recursively
        $interfaceParser = new InterfaceParser();
        $cache = Cache::getInstance();
        $files = $cache->getFiles();
        $ancestorDefinitions = array();
        foreach ($ancestors as $key => $ancestor) {

            // Do we have this pestering leading \?
            if (strpos($ancestor, '\\') === 0) {

                $ancestor = ltrim($ancestor, '\\');
            }

            // Do we know this file?
            if (isset($files[$ancestor])) {

                $ancestorDefinitions[$key] = $interfaceParser->getDefinitionFromFile($files[$ancestor]['path'], $ancestor);
                $ancestorDefinitions[$key]->finalize();
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

            foreach($ancestorDefinitions as $ancestorDefinition) {

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
}