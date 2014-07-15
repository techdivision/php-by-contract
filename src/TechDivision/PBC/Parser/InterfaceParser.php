<?php
/**
 * File containing the InterfaceParser class
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

use TechDivision\PBC\Entities\Definitions\InterfaceDefinition;
use TechDivision\PBC\Entities\Definitions\FileDefinition;
use TechDivision\PBC\Entities\Lists\StructureDefinitionList;

/**
 * TechDivision\PBC\Parser\InterfaceParser
 *
 * The InterfaceParser class which is used to get an \TechDivision\PBC\Entities\Definitions\InterfaceDefinition
 * instance (or several) from a fail containing those definition(s)
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Parser
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */
class InterfaceParser extends AbstractStructureParser
{

    /**
     * Will return a structure definition. If a name is gives method will search for this particular structure.
     *
     * @param null|string $interfaceName Name of a certain structure we are searching for
     * @param boolean     $getRecursive  Will recursively load all conditions of ancestral structures
     *
     * @return \TechDivision\PBC\Entities\Definitions\InterfaceDefinition The definition of a the searched interface
     */
    public function getDefinition($interfaceName = null, $getRecursive = false)
    {
        // First of all we need to get the interface tokens
        $tokens = $this->getStructureTokens(T_INTERFACE);

        // Did we get something valuable?
        if ($tokens === false) {

            return false;

        } elseif ($interfaceName === null && count($tokens) > 1) {
            // If we did not get an interface name and we got more than one class we can fail right here
            return false;

        } elseif (count($tokens) === 1) {
            // We got what we came for

            return $this->getDefinitionFromTokens($tokens[0]);

        } elseif (is_string($interfaceName) && count($tokens) > 1) {
            // We are still here, but got an interface name to look for

            foreach ($tokens as $key => $token) {

                // Now iterate over the array and search for the interface we want
                for ($i = 0; $i < count($token); $i++) {

                    if (is_array($token[$i]) && $token[$i] === T_INTERFACE && $token[$i + 2] === $interfaceName) {

                        return $this->getDefinitionFromTokens($tokens[$key]);
                    }
                }
            }
        }

        // Still here? Must be an error.
        return false;
    }

    /**
     * Will return a list of structures found in a certain file
     *
     * @param string         $file           The path of the file to search in
     * @param FileDefinition $fileDefinition Definition of the file to pick details from
     * @param bool           $getRecursive   Do we need our ancestral information?
     *
     * @return StructureDefinitionList
     */
    public function getDefinitionListFromFile($file, FileDefinition $fileDefinition, $getRecursive = false)
    {
        // Get all the token arrays for the different classes
        $tokens = $this->getStructureTokens($file, T_INTERFACE);

        // Did we get the right thing?
        if (!is_array($tokens)) {

            return false;
        }

        $structureDefinitionList = new StructureDefinitionList();
        foreach ($tokens as $token) {

            try {

                $structureDefinitionList->add($this->getDefinitionFromTokens($token, $fileDefinition));

            } catch (\UnexpectedValueException $e) {
                // Just try the next one

                continue;
            }
        }

        return $structureDefinitionList;
    }

    /**
     * Get the name of the interface
     *
     * @param array $tokens The token array
     *
     * @return string
     *
     * TODO move this to the abstract parent parser
     */
    protected function getName($tokens)
    {
        // Check the tokens
        $className = '';
        for ($i = 0; $i < count($tokens); $i++) {

            // If we got the class name
            if ($tokens[$i][0] === T_INTERFACE) {

                for ($j = $i + 1; $j < count($tokens); $j++) {

                    if ($tokens[$j] === '{') {

                        $className = $tokens[$i + 2][1];
                    }
                }
            }
        }

        // Return what we did or did not found
        return $className;
    }

    /**
     * Will get all parent interfaces (is any).
     * Might return false on error
     *
     * @param array $tokens The token array
     *
     * @return array|boolean
     */
    protected function getParents($tokens)
    {
        // Check the tokens
        $interfaceString = '';
        for ($i = 0; $i < count($tokens); $i++) {

            // If we got the interface name
            if ($tokens[$i][0] === T_EXTENDS) {

                for ($j = $i + 1; $j < count($tokens); $j++) {

                    if ($tokens[$j] === '{') {

                        // We got everything
                        break;

                    } elseif ($tokens[$j][0] === T_STRING) {

                        $interfaceString .= $tokens[$j][1];
                    }
                }
            }
        }

        // Normally we will have one or several interface names separated by commas
        $parents = explode(',', $interfaceString);

        // Did we get something useful?
        if (is_array($parents)) {

            foreach ($parents as $key => $parent) {

                $parents[$key] = trim($parent);

                // We do not want empty stuff
                if (empty($parents[$key])) {

                    unset($parents[$key]);
                }
            }

            return $parents;

        } else {

            return false;
        }
    }

    /**
     * Returns a ClassDefinition from a token array.
     *
     * This method will use a set of other methods to parse a token array and retrieve any
     * possible information from it. This information will be entered into a ClassDefinition object.
     *
     * @param array   $tokens       The token array
     * @param boolean $getRecursive Do we have to load the inherited contracts as well?
     *
     * @return \TechDivision\PBC\Entities\Definitions\InterfaceDefinition
     */
    protected function getDefinitionFromTokens($tokens, $getRecursive = true)
    {
        // First of all we need a new ClassDefinition to fill
        $this->currentDefinition = new InterfaceDefinition();

        // Save the path of the original definition for later use
        $this->currentDefinition->path = $this->file;

        // Get the interfaces own namespace and the namespace which are included via use
        $this->currentDefinition->namespace = $this->getNamespace();
        $this->currentDefinition->usedNamespaces = $this->getUsedNamespaces();

        // For our next step we would like to get the doc comment (if any)
        $this->currentDefinition->docBlock = $this->getDocBlock($tokens, T_INTERFACE);

        // Get the interface identity
        $this->currentDefinition->name = $this->getName($tokens);

        // So we got our docBlock, now we can parse the invariant annotations from it
        $annotationParser = new AnnotationParser($this->file, $this->config, $this->tokens);
        $this->currentDefinition->invariantConditions = $annotationParser->getConditions(
            $this->currentDefinition->docBlock,
            PBC_KEYWORD_INVARIANT
        );

        // Lets check if there is any inheritance, or if we implement any interfaces
        $parentNames = $this->getParents($tokens);
        if (count($this->currentDefinition->usedNamespaces) === 0) {

            foreach ($parentNames as $parentName) {

                if (strpos($parentName, '\\') !== false) {

                    $this->currentDefinition->extends[] = $parentName;

                } else {

                    $this->currentDefinition->extends[] = '\\' . $this->currentDefinition->namespace . '\\' . $parentName;
                }
            }

        } else {

            foreach ($this->currentDefinition->usedNamespaces as $alias) {

                foreach ($parentNames as $parentName) {

                    if (strpos($alias, $parentName) !== false) {

                        $this->currentDefinition->extends = '\\' . $alias;
                    }
                }
            }
        }

        // Clean possible double-\
        $this->currentDefinition->extends = str_replace('\\\\', '\\', $this->currentDefinition->extends);

        $this->currentDefinition->constants = $this->getConstants($tokens);

        // Only thing still missing are the methods, so ramp up our FunctionParser
        $functionParser = new FunctionParser(
            $this->file,
            $this->config,
            $this->structureDefinitionHierarchy,
            $this->structureMap,
            $this->currentDefinition,
            $this->tokens
        );

        $this->currentDefinition->functionDefinitions = $functionParser->getDefinitionListFromTokens($tokens);

        return $this->currentDefinition;
    }
}
