<?php

namespace TechDivision\PBC\Parser;

use TechDivision\PBC\Entities\Definitions\InterfaceDefinition;
use TechDivision\PBC\Entities\Definitions\FileDefinition;
use TechDivision\PBC\Entities\Lists\StructureDefinitionList;
use TechDivision\PBC\Interfaces\StructureParserInterface;

class InterfaceParser extends AbstractParser implements StructureParserInterface
{

    /**
     * @param $file
     * @param null $className
     * @return bool|void
     */
    public function getDefinitionFromFile($file, $interfaceName = null)
    {
        $fileParser = new FileParser();
        $fileDefinition = $fileParser->getDefinitionFromFile($file);

        // First of all we need to get the interface tokens
        $tokens = $this->getStructureTokens($file, T_INTERFACE);

        // Did we get something valuable?
        if ($tokens === false) {

            return false;

        } elseif ($interfaceName === null && count($tokens) > 1) {
            // If we did not get an interface name and we got more than one class we can fail right here
            return false;

        } elseif (count($tokens) === 1) {
            // We got what we came for

            return $this->getDefinitionFromTokens($tokens[0], $fileDefinition);

        } elseif (is_string($interfaceName) && count($tokens) > 1) {
            // We are still here, but got an interface name to look for

            foreach ($tokens as $key => $token) {

                // Now iterate over the array and search for the interface we want
                for ($i = 0; $i < count($token); $i++) {

                    if (is_array($token[$i]) && $token[$i] === T_INTERFACE && $token[$i + 2] === $interfaceName) {

                        return $this->getDefinitionFromTokens($tokens[$key], $fileDefinition);
                    }
                }
            }
        }

        // Still here? Must be an error.
        return false;
    }

    /**
     * @param $file
     * @param FileDefinition $fileDefinition
     * @return bool|StructureDefinitionList
     */
    public function getDefinitionListFromFile($file, FileDefinition $fileDefinition)
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
     * @param $tokens
     * @return string
     */
    private function getName($tokens)
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
     * @param $tokens
     * @return string
     */
    private function getParents($tokens)
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
     * @access private
     * @param $tokens
     * @return FileDefinition
     */
    private function getDefinitionFromTokens($tokens, FileDefinition $fileDefinition)
    {
        // First of all we need a new ClassDefinition to fill
        $interfaceDefinition = new InterfaceDefinition();

        // For our next step we would like to get the doc comment (if any)
        $interfaceDefinition->docBlock = $this->getDocBlock($tokens, T_INTERFACE);

        // So we got our docBlock, now we can parse the invariant annotations from it
        $annotationParser = new AnnotationParser();
        $interfaceDefinition->invariantConditions = $annotationParser->getConditions(
            $interfaceDefinition->docBlock,
            PBC_KEYWORD_INVARIANT
        );

        // Get the class identity
        $interfaceDefinition->name = $this->getName($tokens);

        // Lets check if there is any inheritance, or if we implement any interfaces

        $parentNames = $this->getParents($tokens);
        if (count($fileDefinition->usedNamespaces) === 0) {

            foreach ($parentNames as $parentName) {

                if (strpos($parentName, '\\') !== false) {

                    $interfaceDefinition->extends[] = $parentName;

                } else {

                    $interfaceDefinition->extends[] = '\\' . $fileDefinition->namespace . '\\' . $parentName;
                }
            }

        } else {

            foreach ($fileDefinition->usedNamespaces as $alias) {

                foreach ($parentNames as $parentName) {

                    if (strpos($alias, $parentName) !== false) {

                        $interfaceDefinition->extends = '\\' . $alias;
                    }
                }
            }
        }

        // Clean possible double-\
        $interfaceDefinition->extends = str_replace('\\\\', '\\', $interfaceDefinition->extends);

        $interfaceDefinition->constants = $this->getConstants($tokens);

        // Only thing still missing are the methods, so ramp up our FunctionParser
        $functionParser = new FunctionParser();
        $interfaceDefinition->functionDefinitions = $functionParser->getDefinitionListFromTokens($tokens);

        return $interfaceDefinition;
    }

}