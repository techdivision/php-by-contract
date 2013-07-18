<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 16.07.13
 * Time: 14:05
 * To change this template use File | Settings | File Templates.
 */
namespace TechDivision\PBC\Entities\Definitions;

/**
 * Class ParameterDefinition
 */
class ParameterDefinition
{
    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $name;

    /**
     * Default constructor
     */
    public function __construct()
    {
        $this->type = '';
        $this->name = '';
    }
}