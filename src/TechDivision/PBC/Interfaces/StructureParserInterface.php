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

    public function getDefinitionFromFile($file, $structureName = null);

    public function getDefinitionListFromFile($file, FileDefinition $fileDefinition);
}