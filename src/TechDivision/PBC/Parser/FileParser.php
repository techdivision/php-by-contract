<?php
/**
 * File containing the FileParser class
 *
 * PHP version 5
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Parser
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */

namespace TechDivision\PBC\Parser;

use TechDivision\PBC\Entities\Definitions\FileDefinition;
use TechDivision\PBC\Entities\Lists\StructureDefinitionList;

/**
 * TechDivision\PBC\Parser\FileParser
 *
 * The FileParser class which is used to get a FileDefinition instance from a raw file containing structure
 * definitions
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Parser
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 *
 * TODO Do we even need this class anymore? If yes, refactor token array usage
 */
class FileParser extends AbstractParser
{
    /**
     * Will return the definition of a specified file
     *
     * @param null|string $file         The path of the file we are searching for
     * @param boolean     $getRecursive Do we have to get the ancestral contents as well?
     *
     * @return boolean|\TechDivision\PBC\Entities\Definitions\FileDefinition
     */
    public function getDefinitionFromFile($file, $getRecursive = null)
    {
        // We need and instance first of all
        $fileDefinition = new FileDefinition();

        // If the file is not readable we can forget it right away
        if (!is_readable($file)) {

            return false;
        }

        // We are still here, so we can begin with setting the obvious values
        $fileDefinition->name = basename($file);
        $fileDefinition->path = dirname($file);

        // As there can be several class definitions within a file, but only one namespace or
        // one set of use statements, we will get those here, even if they in the end belong to the class
        $tokens = token_get_all(file_get_contents($file));
        $fileDefinition->namespace = $this->getNamespace($tokens);
        $fileDefinition->usedNamespaces = $this->getUsedNamespaces($tokens);

        // If the file does indeed contain valid PHP structures we can continue.
        // But first we have to check which one.
        $structureType = $this->getStructureToken($tokens);

        // Now we can check which kind of parser we need.
        $parserName = __NAMESPACE__ . '\\' . ucfirst($structureType) . 'Parser';

        // Does this parser exist?
        if (!class_exists($parserName)) {

            return false;
        }

        // Still here? Create an instance of this parser.
        $parser = new $parserName($this->structureMap, $this->structureDefinitionHierarchy);

        $structureDefinitions = $parser->getDefinitionListFromFile($file, $fileDefinition, $getRecursive);

        // Did we get the right thing?
        if ($structureDefinitions instanceof StructureDefinitionList) {

            $fileDefinition->structureDefinitions = $structureDefinitions;
        }

        return $fileDefinition;
    }

    /**
     * Will return the files's namespace if found
     *
     * @param array $tokens The token array
     *
     * @return string
     */
    private function getNamespace($tokens)
    {
        // Check the tokens
        $namespace = '';
        for ($i = 0; $i < count($tokens); $i++) {

            // If we got the namespace
            if ($tokens[$i][0] === T_NAMESPACE) {

                for ($j = $i + 1; $j < count($tokens); $j++) {

                    if ($tokens[$j][0] === T_STRING) {

                        $namespace .= '\\' . $tokens[$j][1];

                    } elseif ($tokens[$j] === '{' || $tokens[$j] === ';' || $tokens[$j][0] === T_CURLY_OPEN) {

                        break;
                    }
                }
            }
        }

        // Return what we did or did not found
        return substr($namespace, 1);
    }

    /**
     * Will return an array of structures which this structure references by use statements
     *
     * @param array $tokens The token array
     *
     * @return array
     *
     * TODO namespaces does not make any sense here, as we are referencing structures!
     */
    private function getUsedNamespaces($tokens)
    {
        // Check the tokens
        $namespaces = array();
        for ($i = 0; $i < count($tokens); $i++) {

            // If we got a use statement
            if ($tokens[$i][0] === T_USE) {

                $namespace = '';
                for ($j = $i + 1; $j < count($tokens); $j++) {

                    if ($tokens[$j][0] === T_STRING) {

                        $namespace .= '\\' . $tokens[$j][1];

                    } elseif ($tokens[$j] === '{' || $tokens[$j] === ';' || $tokens[$j][0] === T_CURLY_OPEN) {

                        $namespaces[] = $namespace;
                        break;
                    }
                }
            }
        }

        // Return what we did or did not found
        return $namespaces;
    }
}
