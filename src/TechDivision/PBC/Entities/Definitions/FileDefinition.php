<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 20.06.13
 * Time: 14:43
 * To change this template use File | Settings | File Templates.
 */
namespace TechDivision\PBC\Entities\Definitions;

use TechDivision\PBC\Entities\Lists\StructureDefinitionList;

/**
 * Class FileDefinition
 */
class FileDefinition
{
    /**
     * @var string
     */
    public $path;

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
    public $usedNamespaces;

    /**
     * @var StructureDefinitionList
     */
    public $structureDefinitions;

    /**
     * Default constructor
     */
    public function __construct()
    {
        $this->path = '';
        $this->name = '';
        $this->namespace = '';
        $this->usedNamespaces = array();
        $this->structureDefinitions = new StructureDefinitionList();
    }
}
