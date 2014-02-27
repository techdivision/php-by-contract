<?php
/**
 * File containing the AbstractStructureParser class
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

use TechDivision\PBC\Interfaces\StructureDefinitionInterface;
use TechDivision\PBC\Interfaces\StructureParserInterface;
use TechDivision\PBC\Exceptions\ParserException;
use TechDivision\PBC\Entities\Definitions\StructureDefinitionHierarchy;
use TechDivision\PBC\StructureMap;

/**
 * TechDivision\PBC\Parser\AbstractStructureParser
 *
 * The abstract class AbstractStructureParser which provides a basic implementation other stucture parsers
 * can inherit from
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
abstract class AbstractStructureParser extends AbstractParser implements StructureParserInterface
{
    /**
     * @var \TechDivision\PBC\StructureMap $structureMap Our structure map instance
     */
    protected $structureMap;

    /**
     * Default constructor
     *
     * @param string                         $file                          The path of the file we want to parse
     * @param \TechDivision\PBC\StructureMap $structureMap                  Our structure map instance
     * @param StructureDefinitionHierarchy   &$structureDefinitionHierarchy The list structures we did
     * @param array                          $tokens                        The array of tokens taken from the file
     */
    public function __construct(
        $file,
        StructureMap $structureMap,
        StructureDefinitionHierarchy & $structureDefinitionHierarchy,
        array $tokens = array()
    ) {

        $this->structureMap = $structureMap;
        $this->structureDefinitionHierarchy = $structureDefinitionHierarchy;

        // We need the parent __construct process
        parent::__construct($file, $tokens);
    }

    /**
     * Will check the main token array for the occurrence of a certain on (class, interface or trait)
     *
     * @return string|boolean
     */
    protected function getStructureToken()
    {
        for ($i = 0; $i < $this->tokenCount; $i++) {

            switch ($this->tokens[$i][0]) {

                case T_CLASS:

                    return 'class';
                    break;

                case T_INTERFACE:

                    return 'interface';
                    break;

                case T_TRAIT:

                    return 'trait';
                    break;

                default:

                    continue;
                    break;
            }
        }

        // We are still here? That should not be.
        return false;
    }

    /**
     * Will check if a certain structure was mentioned in one(!) use statement.
     *
     * @param StructureDefinitionInterface &$structureDefinition The structure $structureName is compared against
     * @param string                       $structureName        The name of the structure we have to check against the
     *                                                           use statements of the definition
     *
     * @return bool|string
     */
    protected function resolveUsedNamespace(StructureDefinitionInterface & $structureDefinition, $structureName)
    {
        // If there was no useful name passed we can fail right here
        if (empty($structureName)) {

            return false;
        }

        // Walk over all namespaces and if we find something we will act accordingly.
        $result = $structureDefinition->getQualifiedName();
        foreach ($structureDefinition->getUsedNamespaces() as $key => $usedNamespace) {

            // Check if the last part of the use statement is our structure
            $tmp = explode('\\', $usedNamespace);
            if (array_pop($tmp) === $structureName) {

                // Tell them we succeeded
                return trim(implode('\\', $tmp) . '\\' . $structureName, '\\');
            }
        }

        // We did not seem to have found anything. Might it be that we are in our own namespace?
        if ($structureDefinition->getNamespace() !== null && strpos($structureName, '\\') !== 0) {

            return $structureDefinition->getNamespace() . '\\' . $structureName;
        }

        // Still here? Return what we got.
        return $result;
    }

    /**
     * Will return the constants within the main token array
     *
     * @return array
     */
    protected function getConstants()
    {
        // Check the tokens
        $constants = array();
        for ($i = 0; $i < $this->tokenCount; $i++) {

            // If we got the class name
            if ($this->tokens[$i][0] === T_CONST) {

                for ($j = $i + 1; $j < $this->tokenCount; $j++) {

                    if ($this->tokens[$j] === ';') {

                        break;

                    } elseif ($this->tokens[$j][0] === T_STRING) {

                        $constants[$this->tokens[$j][1]] = '';

                        for ($k = $j + 1; $k < count($this->tokens); $k++) {

                            if ($this->tokens[$k] === ';') {

                                break;

                            } elseif (is_array($this->tokens[$k]) && $this->tokens[$k][0] !== '=') {

                                $constants[$this->tokens[$j][1]] .= $this->tokens[$k][1];
                            }
                        }

                        // Now trim what we got
                        $constants[$this->tokens[$j][1]] = trim($constants[$this->tokens[$j][1]]);
                    }
                }
            }
        }

        // Return what we did or did not found
        return $constants;
    }

    /**
     * Will return a subset of our main token array. This subset includes all tokens belonging to a certain structure.
     * Might return false on failure
     *
     * @param integer $structureToken The structure we are after e.g. T_CLASS, use PHP tokens here
     *
     * @return array|boolean
     */
    protected function getStructureTokens($structureToken)
    {
        // Now iterate over the array and filter different classes from it
        $result = array();
        for ($i = 0; $i < $this->tokenCount; $i++) {

            // If we got a class keyword, we have to check how far the class extends,
            // then copy the array withing that bounds
            if (is_array($this->tokens[$i]) && $this->tokens[$i][0] === $structureToken) {

                // The lower bound should be the last semicolon|closing curly bracket|PHP tag before the class
                $lowerBound = 0;
                for ($j = $i - 1; $j >= 0; $j--) {

                    if ($this->tokens[$j] === ';' || $this->tokens[$j] === '}' ||
                        is_array($this->tokens[$j]) && $this->tokens[$j][0] === T_OPEN_TAG
                    ) {

                        $lowerBound = $j;
                        break;
                    }
                }

                // The upper bound should be the first time the curly brackets are even again
                $upperBound = $this->tokenCount - 1;
                $bracketCounter = null;
                for ($j = $i + 1; $j < count($this->tokens); $j++) {

                    if ($this->tokens[$j] === '{' || $this->tokens[$j][0] === T_CURLY_OPEN) {

                        // If we still got null set to 0
                        if ($bracketCounter === null) {

                            $bracketCounter = 0;
                        }

                        $bracketCounter++;

                    } elseif ($this->tokens[$j] === '}') {

                        // If we still got null set to 0
                        if ($bracketCounter === null) {

                            $bracketCounter = 0;
                        }

                        $bracketCounter--;
                    }

                    // Do we have an even amount of brackets yet?
                    if ($bracketCounter === 0) {

                        $upperBound = $j;
                        break;
                    }
                }

                $result[] = array_slice($this->tokens, $lowerBound, $upperBound - $lowerBound);
            }
        }

        // Last line of defence; did we get something?
        if (empty($result)) {

            return false;
        }

        return $result;
    }

    /**
     * Will return the structure's namespace if found
     *
     * @return string
     */
    protected function getNamespace()
    {
        // Check the tokens
        $namespace = '';
        for ($i = 0; $i < $this->tokenCount; $i++) {

            // If we got the namespace
            if ($this->tokens[$i][0] === T_NAMESPACE) {

                for ($j = $i + 1; $j < count($this->tokens); $j++) {

                    if ($this->tokens[$j][0] === T_STRING) {

                        $namespace .= '\\' . $this->tokens[$j][1];

                    } elseif ($this->tokens[$j] === '{' ||
                        $this->tokens[$j] === ';' ||
                        $this->tokens[$j][0] === T_CURLY_OPEN
                    ) {

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
     * @return array
     *
     * TODO namespaces does not make any sense here, as we are referencing structures!
     */
    protected function getUsedNamespaces()
    {
        // Check the tokens
        $namespaces = array();
        for ($i = 0; $i < $this->tokenCount; $i++) {

            // If we got a use statement
            if ($this->tokens[$i][0] === T_USE) {

                $namespace = '';
                for ($j = $i + 1; $j < count($this->tokens); $j++) {

                    if ($this->tokens[$j][0] === T_STRING) {

                        $namespace .= '\\' . $this->tokens[$j][1];

                    } elseif ($this->tokens[$j] === '{' ||
                        $this->tokens[$j] === ';' ||
                        $this->tokens[$j][0] === T_CURLY_OPEN
                    ) {

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
