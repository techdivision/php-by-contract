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
use TechDivision\PBC\Proxies\Cache;

/**
 * Class ClassDefinition
 */
class ClassDefinition
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
     *
     */
    public function getAncestralInvariant()
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
                $isChild = $parent->getAncestralInvariant();

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