<?php
/**
 * TechDivision\PBC\Parser\StructureParserFactory
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 */
namespace TechDivision\PBC\Parser;

use TechDivision\PBC\StructureMap;
use TechDivision\PBC\Entities\Definitions\StructureDefinitionHierarchy;
use TechDivision\PBC\Exceptions\ParserException;

/**
 * @package     TechDivision\PBC
 * @subpackage  Parser
 * @copyright   Copyright (c) 2013 <info@techdivision.com> - TechDivision GmbH
 * @license     http://opensource.org/licenses/osl-3.0.php
 *              Open Software License (OSL 3.0)
 * @author      Bernhard Wick <b.wick@techdivision.com>
 */
class StructureParserFactory
{
    /**
     * @param $type
     *
     * @return string
     */
    public function getClassName($type)
    {
        return $this->getName($type);
    }

    /**
     * @param                              $type
     * @param                              $file
     * @param StructureMap                 $structureMap
     * @param StructureDefinitionHierarchy $structureDefinitionHierarchy
     *
     * @return mixed
     */
    public function getInstance(
        $type,
        $file,
        StructureMap $structureMap,
        StructureDefinitionHierarchy & $structureDefinitionHierarchy
    ) {
        $name = $this->getName($type);

        return new $name($file, $structureMap, $structureDefinitionHierarchy);
    }

    /**
     * @param $type
     *
     * @return string
     * @throws ParserException
     */
    private function getName($type)
    {
        // What kind of exception do we need?
        switch ($type) {

            case 'class':

                $name = 'ClassParser';
                break;

            case 'interface':

                $name = 'InterfaceParser';
                break;

            default:

                throw new ParserException('Unknown parser type ' . $type);
                break;
        }

        if (class_exists(__NAMESPACE__ . '\\' . $name)) {

            return __NAMESPACE__ . '\\' . $name;
        }
    }
}
