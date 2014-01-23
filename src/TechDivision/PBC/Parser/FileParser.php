<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 26.06.13
 * Time: 13:18
 * To change this template use File | Settings | File Templates.
 */

namespace TechDivision\PBC\Parser;

use TechDivision\PBC\Entities\Definitions\FileDefinition;
use TechDivision\PBC\Entities\Lists\StructureDefinitionList;

/**
 * Class FileParser
 */
class FileParser extends AbstractParser
{
    /**
     * @param $file
     * @param bool $getRecursive
     * @return bool|FileDefinition
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
     * @param $tokens
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
     * @param $tokens
     *
     * @return array
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


    /**
     * @param $file
     * @return bool
     */
    private function containsValidClass($file)
    {
        // Get the tokens
        $tokens = token_get_all(file_get_contents($file));

        // If we do not get valid PHP $tokens will only have one element
        if (count($tokens) === 1) {

            return false;
        }

        // Lets iterate over all tokens and check for any class declarations
        $bracketCounter = null;
        for ($i = 0; $i < count($tokens); $i++) {

            // If we found a class keyword we have to count the opening and closing curly brackets after it.
            // If the number is even we should have a valid class.
            if (is_array($tokens[$i]) && $tokens[$i][0] === T_CLASS) {

                // Reset our counter
                $bracketCounter = 0;

                // We got something, lets count the brackets between it and our variable's position
                for ($j = $i + 1; $j < count($tokens); $j++) {

                    if ($tokens[$j] === '{' || $tokens[$j][0] === T_CURLY_OPEN) {

                        $bracketCounter++;

                    } elseif ($tokens[$j] === '}') {

                        $bracketCounter--;
                    }
                }

                // We do not need to continue the outer for
                break;
            }
        }

        // Even number of brackets?
        if (isset($bracketCounter) && $bracketCounter === 0) {

            return true;

        } else {

            return false;
        }
    }
}