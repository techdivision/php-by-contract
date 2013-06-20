<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 20.06.13
 * Time: 14:43
 * To change this template use File | Settings | File Templates.
 */
namespace TechDivision\PBC\Entities;

/**
 * Class ScriptDefinition
 */
class ScriptDefinition
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $filePath;

    /**
     * @var FunctionDefinitionList
     */
    public $functionDefinitions;
}