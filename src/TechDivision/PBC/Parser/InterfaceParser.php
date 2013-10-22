<?php

namespace TechDivision\PBC\Parser;

class InterfaceParser extends AbstractParser
{

    /**
     * @param $file
     * @return bool|StructureDefinitionList
     */
    public function getDefinitionListFromFile($file, FileDefinition $fileDefinition)
    {
        // Get all the token arrays for the different classes
        $tokens = $this->getClassTokens($file);

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
     * Returns a ClassDefinition from a token array.
     *
     * This method will use a set of other methods to parse a token array and retrieve any
     * possible information from it. This information will be entered into a ClassDefinition object.
     *
     * @access private
     * @param $tokens
     * @return ClassDefinition
     */
    private function getDefinitionFromTokens($tokens, FileDefinition $fileDefinition)
    {
        // First of all we need a new ClassDefinition to fill
        $classDefinition = new ClassDefinition();

        // For our next step we would like to get the doc comment (if any)
        $classDefinition->docBlock = $this->getDocBlock($tokens);

        // So we got our docBlock, now we can parse the invariant annotations from it
        $annotationParser = new AnnotationParser();
        $classDefinition->invariantConditions = $annotationParser->getConditions($classDefinition->docBlock, PBC_KEYWORD_INVARIANT);

        // Get the class identity
        $classDefinition->isFinal = $this->isFinalClass($tokens);
        $classDefinition->isAbstract = $this->isAbstractClass($tokens);
        $classDefinition->name = $this->getClassName($tokens);

        // Lets check if there is any inheritance, or if we implement any interfaces

        $parentName = $this->getParent($tokens);
        if ($parentName === '') {

            $classDefinition->extends = $parentName;

        } elseif (count($fileDefinition->usedNamespaces) === 0) {

            if (strpos($parentName, '\\') !== false) {

                $classDefinition->extends = $parentName;

            } else {

                $classDefinition->extends = '\\' . $fileDefinition->namespace . '\\' . $parentName;
            }

        } else {

            foreach($fileDefinition->usedNamespaces as $alias) {

                if (strpos($alias, $parentName) !== false) {

                    $classDefinition->extends = '\\' . $alias;
                }
            }
        }

        // Clean possible double-\
        $classDefinition->extends = str_replace('\\\\', '\\', $classDefinition->extends);

        $classDefinition->implements = $this->getInterfaces($tokens);

        $classDefinition->constants = $this->getConstants($tokens);

        // Lets get the attributes the class might have
        $classDefinition->attributeDefinitions = $this->getAttributes($tokens);

        // Only thing still missing are the methods, so ramp up our FunctionParser
        $functionParser = new FunctionParser();
        $classDefinition->functionDefinitions = $functionParser->getDefinitionListFromTokens($tokens);

        return $classDefinition;
    }

}