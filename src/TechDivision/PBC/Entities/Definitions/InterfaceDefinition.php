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
use TechDivision\PBC\Interfaces\StructureDefinitionInterface;
use TechDivision\PBC\Parser\InterfaceParser;
use TechDivision\PBC\Config;
use TechDivision\PBC\StructureMap;

/**
 * Class InterfaceDefinition
 */
class InterfaceDefinition implements StructureDefinitionInterface
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
     */
    public function __construct()
    {
        $this->docBlock = '';
        $this->name = '';
        $this->namespace = '';
        $this->extends = array();
        $this->constants = array();
        $this->invariantConditions = new AssertionList();
        $this->ancestralInvariants = new TypedListList();
        $this->functionDefinitions = new FunctionDefinitionList();
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