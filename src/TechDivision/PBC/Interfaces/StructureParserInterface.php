<?php
/**
 * File containing the StructureParserInterface interface
 *
 * PHP version 5
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Interfaces
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */

namespace TechDivision\PBC\Interfaces;

use TechDivision\PBC\Entities\Definitions\FileDefinition;
use TechDivision\PBC\Entities\Lists\StructureDefinitionList;

/**
 * TechDivision\PBC\Interfaces\StructureParserInterface
 *
 * Interface which describes parsers for structures like classes, interfaces and traits.
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Interfaces
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */
interface StructureParserInterface extends ParserInterface
{
    /**
     * Will return a structure definition. If a name is gives method will search for this particular structure.
     *
     * @param null $structureName Name of a certain structure we are searching for
     * @param bool $getRecursive  Will recursively load all conditions of ancestral structures
     *
     * @return StructureDefinitionInterface The definition of a the searched structure
     */
    public function getDefinition($structureName = null, $getRecursive = true);

    /**
     * Will return a list of structures found in a certain file
     *
     * @param string         $file           The path of the file to search in
     * @param FileDefinition $fileDefinition Definition of the file to pick details from
     * @param bool           $getRecursive   Do we need our ancestral information?
     *
     * @return StructureDefinitionList
     */
    public function getDefinitionListFromFile($file, FileDefinition $fileDefinition, $getRecursive = true);
}
