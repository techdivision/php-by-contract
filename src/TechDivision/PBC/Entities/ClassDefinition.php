<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 20.06.13
 * Time: 10:31
 * To change this template use File | Settings | File Templates.
 */
namespace TechDivision\PBC\Entities;

/**
 * Class ClassDefinition
 */
class ClassDefinition
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $namespace;

    /**
     * @var string
     */
    public $filePath;

    /**
     * @var FunctionDefinitionList
     */
    public $functionDefinitions;

    /**
     * @var AssertionList
     */
    public $invariantConditions;
}