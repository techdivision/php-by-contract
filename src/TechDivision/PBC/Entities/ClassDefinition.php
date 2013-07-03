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
     * @var array
     */
    public $attributes;

    /**
     * @var AssertionList
     */
    public $invariantConditions;

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
        $this->namespace = '';
        $this->attributes = array();
        $this->invariantConditions = new AssertionList();
        $this->docBlock = '';
    }
}