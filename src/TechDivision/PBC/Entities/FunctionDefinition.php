<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 19.06.13
 * Time: 16:01
 * To change this template use File | Settings | File Templates.
 */

namespace TechDivision\PBC\Entities;

/**
 * Class FunctionDefinition
 */
class FunctionDefinition
{
    /**
     * @var string
     */
    public $class;

    /**
     * @var string
     */
    public $name;

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
     * @var boolean
     */
    public $usesResult;
}