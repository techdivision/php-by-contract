<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 26.06.13
 * Time: 13:19
 * To change this template use File | Settings | File Templates.
 */

namespace TechDivision\PBC\Parser;

use TechDivision\PBC\Entities\Definitions\ClassDefinition;
use TechDivision\PBC\Entities\Definitions\FunctionDefinition;
use TechDivision\PBC\Entities\Definitions\FileDefinition;
use TechDivision\PBC\Entities\Definitions\Structure;
use TechDivision\PBC\Entities\Definitions\AttributeDefinition;
use TechDivision\PBC\Entities\Lists\AssertionList;
use TechDivision\PBC\Entities\Lists\StructureDefinitionList;
use TechDivision\PBC\Entities\Lists\AttributeDefinitionList;
use TechDivision\PBC\Entities\Lists\TypedListList;
use TechDivision\PBC\Interfaces\StructureParserInterface;
use TechDivision\PBC\Exceptions\ParserException;

/**
 * Class ClassParser
 */
class ClassParser extends AbstractStructureParser
{
    /**
     * @param $file
     * @param FileDefinition $fileDefinition
     * @param bool $getRecursive
     * @return bool|StructureDefinitionList
     */
    public function getDefinitionListFromFile($file, FileDefinition $fileDefinition, $getRecursive = true)
    {
        // Get all the token arrays for the different classes
        $tokens = $this->getStructureTokens($file, T_CLASS);

        // Did we get the right thing?
        if (!is_array($tokens)) {

            return false;
        }

        $structureDefinitionList = new StructureDefinitionList();
        foreach ($tokens as $token) {

            try {

                $structureDefinitionList->add($this->getDefinitionFromTokens($token, $fileDefinition, $getRecursive));

            } catch (\UnexpectedValueException $e) {
                // Just try the next one

                continue;
            }
        }

        return $structureDefinitionList;
    }

    /**
     * @param null $className
     * @param bool $getRecursive
     * @return bool|StructureDefinition
     */
    public function getDefinition($className = null, $getRecursive = true)
    {
        // First of all we need to get the class tokens
        $tokens = $this->getStructureTokens(T_CLASS);

        // Did we get something valueable?
        if ($tokens === false) {

            return false;

        } elseif ($className === null && count($tokens) > 1) {
            // If we did not get a class name and we got more than one class we can fail right here
            return false;

        } elseif (count($tokens) === 1) {
            // We got what we came for

            return $this->getDefinitionFromTokens($tokens[0], $getRecursive);

        } elseif (is_string($className) && count($tokens) > 1) {
            // We are still here, but got a class name to look for

            foreach ($tokens as $key => $token) {

                // Now iterate over the array and search for the class we want
                for ($i = 0; $i < count($token); $i++) {

                    if (is_array($token[$i]) && $token[$i] === T_CLASS && $token[$i + 2] === $className) {

                        return $this->getDefinitionFromTokens($tokens[$key], $getRecursive);
                    }
                }
            }
        }

        // Still here? Must be an error.
        return false;
    }

    /**
     * Returns a ClassDefinition from a token array.
     *
     * This method will use a set of other methods to parse a token array and retrieve any
     * possible information from it. This information will be entered into a ClassDefinition object.
     *
     * @access private
     * @param $tokens
     * @param bool $getRecursive
     * @return StructureDefinition
     */
    private function getDefinitionFromTokens($tokens, $getRecursive = true)
    {
        // First of all we need a new ClassDefinition to fill
        $classDefinition = new ClassDefinition();

        // Save the path of the original definition for later use
        $classDefinition->path = $this->file;

        // File based namespaces do not make much sense, so hand it over here.
        $classDefinition->namespace = $this->getNamespace();
        $classDefinition->name = $this->getName($tokens);
        $classDefinition->usedNamespaces = $this->getUsedNamespaces();

        // Maybe we already got this structure?
        if ($this->structureDefinitionHierarchy->entryExists($classDefinition->getQualifiedName())) {

            return $this->structureDefinitionHierarchy->getEntry($classDefinition->getQualifiedName());
        }

        // For our next step we would like to get the doc comment (if any)
        $classDefinition->docBlock = $this->getDocBlock($tokens, T_CLASS);

        // So we got our docBlock, now we can parse the invariant annotations from it
        $annotationParser = new AnnotationParser();
        $classDefinition->invariantConditions = $annotationParser->getConditions(
            $classDefinition->docBlock,
            PBC_KEYWORD_INVARIANT
        );

        // Get the class identity
        $classDefinition->isFinal = $this->hasSignatureToken($this->tokens, T_FINAL, T_CLASS);
        $classDefinition->isAbstract = $this->hasSignatureToken($this->tokens, T_ABSTRACT, T_CLASS);

        // Lets check if there is any inheritance, or if we implement any interfaces
        $parentName = $this->getParent($tokens);
        if ($parentName === '') {

            $classDefinition->extends = $parentName;

        } elseif (count($classDefinition->usedNamespaces) === 0) {

            if (strpos($parentName, '\\') !== false) {

                $classDefinition->extends = $parentName;

            } else {

                $classDefinition->extends = '\\' . $classDefinition->namespace . '\\' . $parentName;
            }

        } else {

            foreach ($classDefinition->usedNamespaces as $alias) {

                if (strpos($alias, $parentName) !== false) {

                    $classDefinition->extends = '\\' . $alias;
                }
            }
        }

        // Clean possible double-\
        $classDefinition->extends = trim(
            $this->resolveUsedNamespace(
                $classDefinition->usedNamespaces,
                $classDefinition->namespace,
                str_replace('\\\\', '\\', $classDefinition->extends)
            ),
            '\\'
        );
// Get all the interfaces we have
        $classDefinition->implements = $this->getInterfaces($classDefinition);

        // Get all class constants
        $classDefinition->constants = $this->getConstants($tokens);

        // Lets get the attributes the class might have
        $classDefinition->attributeDefinitions = $this->getAttributes($tokens, $classDefinition->invariantConditions);

        // Only thing still missing are the methods, so ramp up our FunctionParser
        $functionParser = new FunctionParser($this->structureMap, $this->structureDefinitionHierarchy);
        $classDefinition->functionDefinitions = $functionParser->getDefinitionListFromTokens($tokens, $getRecursive);

        // If we have to parse the definition in a recursive manner, we have to get the parent invariants
        if ($getRecursive === true) {

            $dependencies = $classDefinition->getDependencies();
            foreach ($dependencies as $dependency) {

                // frechly set the depency definition to avoid side effects
                $dependencyDefinition = null;

                // Maybe we already got this structure?
                if ($this->structureDefinitionHierarchy->entryExists($classDefinition->getQualifiedName())) {

                    $dependencyDefinition = $this->structureDefinitionHierarchy->getEntry(
                        $classDefinition->getQualifiedName()
                    );

                } else {

                    $fileEntry = $this->structureMap->getEntry($dependency);
                    if (!$fileEntry instanceof Structure) {

                        // Continue, don't fail as we might have dependencies which are not under PBC surveilance
                        continue;
                    }

                    // If we do not have a class we need a different parser for this dependency
                    if ($fileEntry->getType() !== 'class') {

                        // Get the needed parser
                        $structureParserFactory = new StructureParserFactory();
                        $parser = $structureParserFactory->getInstance(
                            $fileEntry->getType(),
                            $fileEntry->getPath(),
                            $this->structureMap,
                            $this->structureDefinitionHierarchy
                        );

                    } else {

                        // If we have a class we can use this parser
                        $parser = $this;
                    }

                    // Get the definition
                    $dependencyDefinition = $parser->getDefinition(
                        $fileEntry->getPath(),
                        $dependency,
                        $getRecursive
                    );
                }

                // Only classes and traits have invariants
                if ($fileEntry->getType() === 'class') {

                    $classDefinition->ancestralInvariants = $dependencyDefinition->getInvariants();
                }

                // Iterate over all dependencies and combine method conditions if method match
                $functionIterator = $classDefinition->functionDefinitions->getIterator();
                foreach($functionIterator as $function) {

                    // Get the ancestral function of the one we currently have a look at.
                    // If we got it we have to get their conditions
                    $ancestralFunction = $dependencyDefinition->functionDefinitions->get($function->name);
                    if ($ancestralFunction instanceof FunctionDefinition) {

                        // If the ancestral function uses the old keyword we have to do too
                        if ($ancestralFunction->usesOld !== false) {

                            $function->usesOld = true;
                        }

                        // Get the conditions
                        $function->ancestralPreconditions = $ancestralFunction->getPreconditions();
                        $function->ancestralPostconditions = $ancestralFunction->getPostconditions();

                        // Save if back into the definition
                        $classDefinition->functionDefinitions->set($function->name, $function);
                    }
                }

                // Finally add the dependency definition to our structure definition hierachry to avoid
                // redundant parsing
                $this->structureDefinitionHierarchy->insert($dependencyDefinition);
            }
        }

        // Before exiting we will add the entry to the current structure definition hierarchy
        $this->structureDefinitionHierarchy->insert($classDefinition);
        return $classDefinition;
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
            if ($tokens[$i][0] === T_CLASS) {

                for ($j = $i + 1; $j < count($tokens); $j++) {

                    if ($tokens[$j] === '{' || $tokens[$j][0] === T_CURLY_OPEN) {

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
    private function getParent($tokens)
    {
        // Check the tokens
        $className = '';
        for ($i = 0; $i < count($tokens); $i++) {

            // If we got the class name
            if ($tokens[$i][0] === T_EXTENDS) {

                for ($j = $i + 1; $j < count($tokens); $j++) {

                    if ($tokens[$j] === '{' || $tokens[$j][0] === T_CURLY_OPEN || $tokens[$j][0] === T_IMPLEMENTS) {

                        return $className;

                    } elseif ($tokens[$j][0] === T_STRING) {

                        $className .= $tokens[$j][1];
                    }
                }
            }
        }

        // Return what we did or did not found
        return $className;
    }

    /**
     * @param ClassDefinition $classDefinition
     * @return array
     */
    private function getInterfaces(ClassDefinition & $classDefinition)
    {
        // Check the tokens
        $interfaces = array();
        for ($i = 0; $i < $this->tokenCount; $i++) {

            // If we got the class name
            if ($this->tokens[$i][0] === T_IMPLEMENTS) {

                for ($j = $i + 1; $j < $this->tokenCount; $j++) {

                    if ($this->tokens[$j] === '{' || $this->tokens[$j][0] === T_CURLY_OPEN || $this->tokens[$j][0] === T_EXTENDS) {

                        return $interfaces;

                    } elseif ($this->tokens[$j][0] === T_STRING) {

                        $interfaces[] = $this->resolveUsedNamespace(
                            $classDefinition->usedNamespaces,
                            $classDefinition->namespace,
                            $this->tokens[$j][1]
                        );
                    }
                }
            }
        }

        // Return what we did or did not found
        return $interfaces;
    }

    /**
     * Retrieves class attributes from token array.
     *
     * This method will search for any attributes a class might have. Just pass the token array of the class.
     * Work is done using token definitions and common sense in regards to PHP syntax.
     * To retrieve the different properties of an attribute it relies on $this::getAttributeProperties().
     *
     * @access private
     * @param array $tokens
     * @param AssertionList $invariants
     * @return AttributeDefinitionList
     */
    private function getAttributes(array $tokens, AssertionList $invariants = null)
    {
        // Check the tokens
        $attributes = new AttributeDefinitionList();
        for ($i = 0; $i < count($tokens); $i++) {

            // If we got a variable we will check if there is any function definition above it.
            // If not, we got an attribute, if so we will check if there is an even number of closing and opening
            // brackets above it, which would mean we are not in the function.
            if (is_array($tokens[$i]) && $tokens[$i][0] === T_VARIABLE) {

                for ($j = $i - 1; $j >= 0; $j--) {

                    if (is_array($tokens[$j]) && $tokens[$j][0] === T_FUNCTION) {

                        // Initialize our counter and also the check if we even started counting
                        $bracketCounter = 0;
                        $usedCounter = false;

                        // We got something, lets count the brackets between it and our variable's position
                        for ($k = $j + 1; $k < $i; $k++) {

                            if ($tokens[$k] === '{' || $tokens[$k][0] === T_CURLY_OPEN) {

                                $usedCounter = true;
                                $bracketCounter++;

                            } elseif ($tokens[$k] === '}') {

                                $usedCounter = true;
                                $bracketCounter--;
                            }
                        }

                        // If we got an even number of brackets (the counter is 0 and got used), we got an attribute
                        if ($bracketCounter === 0 && $usedCounter === true) {

                            $attributes->set($tokens[$i][1], $this->getAttributeProperties($tokens, $i));
                        }

                        break;

                    } elseif (is_array($tokens[$j]) && $tokens[$j][0] === T_CLASS) {
                        // If we reach the class definition without passing a function we definitely got an attribute

                        $attributes->set($tokens[$i][1], $this->getAttributeProperties($tokens, $i));
                        break;
                    }
                }
            }
        }

        // If we got invariants we will check if our attributes are used in invariants
        if ($invariants !== null) {

            // Lets iterate over all the attributes and check them against the invariants we got
            $invariantIterator = $invariants->getIterator();
            $invariantCount = $invariantIterator->count();
            $attributeIterator = $attributes->getIterator();
            for ($i = 0; $i < $attributeIterator->count(); $i++) {

                // Do we have any of these attributes in our invariants?
                $invariantIterator = $invariants->getIterator();
                for ($j = 0; $j < $invariantCount; $j++) {

                    if (strpos(
                            $invariantIterator->current()->getString(),
                            '$this->' . ltrim($attributeIterator->current()->name, '$')
                        ) !== false
                    ) {

                        // Tell them we were mentioned and persist it
                        $attributeIterator->current()->inInvariant = true;
                    }

                    $invariantIterator->next();
                }

                $attributeIterator->next();
            }
        }

        return $attributes;
    }

    /**
     * @param array $tokens
     * @param $attributePosition
     * @return AttributeDefinition
     */
    private function getAttributeProperties(array $tokens, $attributePosition)
    {
        // We got the tokens and the position of the attribute, so look in front of it for visibility and a
        // possible static keyword
        $attribute = new AttributeDefinition();
        $attribute->name = $tokens[$attributePosition][1];

        for ($i = $attributePosition; $i > $attributePosition - 6; $i--) {

            // Search for the visibility
            if (is_array($tokens[$i]) && ($tokens[$i][0] === T_PRIVATE || $tokens[$i][0] === T_PROTECTED)) {

                // Got it!
                $attribute->visibility = $tokens[$i][1];
            }

            // Do we get a static keyword?
            if (is_array($tokens[$i]) && $tokens[$i][0] === T_STATIC) {

                // Class default is false, so set it to true
                $attribute->is_static = true;
            }
        }

        // Now check if there is any default value for this attribute, if so we have to get it
        $defaultValue = null;
        for ($i = $attributePosition; $i < count($tokens); $i++) {

            // If we reach the semicolon we do not have anything here.
            if ($tokens[$i] === ';') {

                break;
            }

            if ($defaultValue !== null) {
                // Do we get a static keyword?
                if (is_array($tokens[$i])) {

                    $defaultValue .= $tokens[$i][1];

                } else {

                    $defaultValue .= $tokens[$i];
                }
            }

            // If we pass a = we have to get ready to make notes
            if ($tokens[$i] === '=') {

                $defaultValue = '';
            }
        }

        // Set the default Value
        $attribute->defaultValue = $defaultValue;

        // Last but not least we have to check if got the visibility, if not, set it public.
        // This is necessary, as missing visibility in the definition will also default to public
        if ($attribute->visibility === '') {

            $attribute->visibility = 'public';
        }

        return $attribute;
    }
}