<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 11.07.13
 * Time: 11:50
 * To change this template use File | Settings | File Templates.
 */

namespace TechDivision\PBC\Entities\Definitions;
use TechDivision\PBC\Interfaces\Definition;

/**
 * Class AttributeDefinition
 */
class AttributeDefinition implements Definition
{
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
     * @var mixed
     */
    public $defaultValue;

    /**
     * Is this attribute part of the invariant?
     *
     * @var bool
     */
    public $inInvariant;

    /**
     * Default constructor
     */
    public function __construct()
    {
        $this->visibility = 'public';
        $this->isStatic = false;
        $this->name = '';
        $this->defaultValue = null;
        $this->inInvariant = false;
    }

    /**
     * @return string
     */
    public function getString()
    {
        $stringParts = array();

        // Set the visibility
        $stringParts[] = $this->visibility;

        // If we are static, we have to tell so
        if ($this->isStatic === true) {

            $stringParts[] = 'static';
        }

        // Add the name
        $stringParts[] = $this->name;

        // Add any default value we might get
        if ($this->defaultValue !== null) {

            $stringParts[] = '= ' . $this->defaultValue;
        }

        // And don't forget the trailing semicolon + linebreak
        $stringParts[] = ';';

        return implode(' ', $stringParts);
    }
}