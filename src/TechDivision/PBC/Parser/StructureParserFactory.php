<?php
/**
 * File containing the StructureParserFactory class
 *
 * PHP version 5
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Parser
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 *             Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */

namespace TechDivision\PBC\Parser;

use TechDivision\PBC\StructureMap;
use TechDivision\PBC\Entities\Definitions\StructureDefinitionHierarchy;
use TechDivision\PBC\Exceptions\ParserException;

/**
 * TechDivision\PBC\Parser\StructureParserFactory
 *
 * This class helps us getting the right parser for different structures
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Parser
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 *             Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */
class StructureParserFactory
{
    /**
     * Will return the name of the parser class for the needed structure type
     *
     * @param string $type The type of exception we need
     *
     * @return string
     */
    public function getClassName($type)
    {
        return $this->getName($type);
    }

    /**
     * Will return an instance of the parser fitting the structure type we specified
     *
     * @param string                                                              $type                          The
     *      structure type we need a parser for
     * @param string                                                              $file                          The
     *      file we want to parse
     * @param \TechDivision\PBC\StructureMap                                      $structureMap                  Struct-
     *      ure map to pass to the parser
     * @param \TechDivision\PBC\Entities\Definitions\StructureDefinitionHierarchy &$structureDefinitionHierarchy The
     *      list of already parsed definitions from the structure's hierarchy
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

        return new $name($file, $structureDefinitionHierarchy, $structureMap);
    }

    /**
     * Find the name of the parser class we need
     *
     * @param string $type The structure type we need a parser for
     *
     * @throws \TechDivision\PBC\Exceptions\ParserException
     *
     * @return string
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
