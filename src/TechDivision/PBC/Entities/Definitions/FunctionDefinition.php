<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 19.06.13
 * Time: 16:01
 * To change this template use File | Settings | File Templates.
 */

namespace TechDivision\PBC\Entities\Definitions;

use TechDivision\PBC\Entities\Lists\AssertionList;
use TechDivision\PBC\Entities\Lists\TypedListList;

/**
 * Class FunctionDefinition
 */
class FunctionDefinition
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
     * @var string
     */
    public $visibility;

    /**
     * @var boolean
     */
    public $isStatic;

    /**
     * @var string
     */
    public $name;

    /**
     * @var ParameterDefinitionList
     */
    public $parameterDefinitions;

    /**
     * @var AssertionList
     */
    public $preConditions;

    /**
     * @var TypedListList
     */
    public $ancestralPreConditions;

    /**
     * @var boolean
     */
    public $usesOld;

    /**
     * @var string
     */
    public $body;

    /**
     * @var AssertionList
     */
    public $postConditions;

    /**
     * @var TypedListList
     */
    public $ancestralPostConditions;

    /**
     * Default constructor
     */
    public function __construct()
    {
        $this->docBlock = '';
        $this->isFinal = false;
        $this->visibility = '';
        $this->isStatic = false;
        $this->name = '';
        $this->parameterDefinitions = array();
        $this->preConditions = new AssertionList();
        $this->ancestralPreConditions = new TypedListList();
        $this->usesOld = false;
        $this->body = '';
        $this->postConditions = new AssertionList();
        $this->ancestralPostConditions = new TypedListList();
    }
}