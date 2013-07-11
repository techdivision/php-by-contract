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

/**
 * Class ClassDefinition
 */
class ClassDefinition
{
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
     * @var AttributeDefinitionList
     */
    public $attributes;

    /**
     * @var AssertionList
     */
    public $invariantConditions;

    /**
     * @var FunctionDefinitionList
     */
    public $functionDefinitions;

    /**
     * Default constructor
     */
    public function __construct()
    {
        $this->namespace = '';
        $this->usedNamespaces = array();
        $this->docBlock = '';
        $this->isFinal = false;
        $this->isAbstract = false;
        $this->name = '';
        $this->attributes = new AttributeDefinitionList();
        $this->invariantConditions = new AssertionList();
        $this->functionDefinitions = new FunctionDefinitionList();
    }
}