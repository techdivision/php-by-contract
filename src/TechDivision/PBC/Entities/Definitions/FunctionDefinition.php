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

/**
 * Class FunctionDefinition
 */
class FunctionDefinition
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $access;

    /**
     * @var array
     */
    public $parameters;

    /**
     * @var AssertionList
     */
    public $preConditions;

    /**
     * @var AssertionList
     */
    public $postConditions;

    /**
     * @var boolean
     */
    public $usesOld;

    /**
     * @var string
     */
    public $docBlock;

    /**
     * Default constructor
     */
    public function __construct()
    {
        $this->name = '';
        $this->access = '';
        $this->parameters = array();
        $this->postConditions = new AssertionList();
        $this->preConditions = new AssertionList();
        $this->usesOld = false;
        $this->docBlock = '';
    }
}