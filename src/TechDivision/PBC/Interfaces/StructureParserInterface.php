<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 16.07.13
 * Time: 12:55
 * To change this template use File | Settings | File Templates.
 */

namespace TechDivision\PBC\Interfaces;

use TechDivision\PBC\Entities\Definitions\FileDefinition;

interface StructureParserInterface extends ParserInterface
{
    /**
     * @param null $structureName
     * @param bool $getRecursive
     * @return mixed
     */
    public function getDefinition($structureName = null, $getRecursive = true);

    /**
     * @param $file
     * @param FileDefinition $fileDefinition
     * @param bool $getRecursive
     * @return mixed
     */
    public function getDefinitionListFromFile($file, FileDefinition $fileDefinition, $getRecursive = true);
}