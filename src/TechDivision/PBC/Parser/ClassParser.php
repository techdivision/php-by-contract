<?php
/**
 * File containing the ClassParser class
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

use TechDivision\PBC\Entities\Definitions\ClassDefinition;
use TechDivision\PBC\Entities\Definitions\FileDefinition;
use TechDivision\PBC\Entities\Definitions\Structure;
use TechDivision\PBC\Entities\Definitions\AttributeDefinition;
use TechDivision\PBC\Entities\Lists\StructureDefinitionList;
use TechDivision\PBC\Entities\Lists\AttributeDefinitionList;
use TechDivision\PBC\Entities\Lists\TypedListList;
use TechDivision\PBC\Interfaces\StructureParserInterface;
use TechDivision\PBC\Interfaces\StructureDefinitionInterface;

/**
 * TechDivision\PBC\Parser\ClassParser
 *
 * This class implements the StructureParserInterface for class structures
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Parser
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */
class ClassParser extends AbstractStructureParser
{
    /**
     * Will return a list of found structures or false on error
     *
     * @param string         $file           Path of the file we are searching in
     * @param FileDefinition $fileDefinition Definition of the file the class is in
     * @param bool           $getRecursive   Do we have to get the ancestral conditions as well?
     *
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
     * Will return the definition of a specified class
     *
     * @param null|string $className    The name of the class we are searching for
     * @param bool        $getRecursive Do we have to get the ancestral conditions as well?
     *
     * @return bool|\TechDivision\PBC\Interfaces\StructureDefinitionInterface
     */
    public function getDefinition($className = null, $getRecursive = true)
    {
        // Maybe we already got this structure?
        if ($this->structureDefinitionHierarchy->entryExists($className)) {

            return $this->structureDefinitionHierarchy->getEntry($className);
        }

        // First of all we need to get the class tokens
        $tokens = $this->getStructureTokens(T_CLASS);

        // Did we get something valuable?
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
     * @param array   $tokens       The token array containing structure tokens
     * @param boolean $getRecursive Do we have to get the ancestral conditions as well?
     *
     * @return \TechDivision\PBC\Interfaces\StructureDefinitionInterface
     */
    protected function getDefinitionFromTokens($tokens, $getRecursive = true)
    {
        // First of all we need a new ClassDefinition to fill
        $this->currentDefinition = new ClassDefinition();

        // Save the path of the original definition for later use
        $this->currentDefinition->path = $this->file;

        // File based namespaces do not make much sense, so hand it over here.
        $this->currentDefinition->namespace = $this->getNamespace();
        $this->currentDefinition->name = $this->getName($tokens);
        $this->currentDefinition->usedNamespaces = $this->getUsedNamespaces();

        // For our next step we would like to get the doc comment (if any)
        $this->currentDefinition->docBlock = $this->getDocBlock($tokens, T_CLASS);

        // Lets get the attributes the class might have
        $this->currentDefinition->attributeDefinitions = $this->getAttributes(
            $tokens
        );

        // So we got our docBlock, now we can parse the invariant annotations from it
        $annotationParser = new AnnotationParser($this->file, $this->tokens, $this->currentDefinition);
        $this->currentDefinition->invariantConditions = $annotationParser->getConditions(
            $this->currentDefinition->getDocBlock(),
            PBC_KEYWORD_INVARIANT
        );

        // Get the class identity
        $this->currentDefinition->isFinal = $this->hasSignatureToken($this->tokens, T_FINAL, T_CLASS);
        $this->currentDefinition->isAbstract = $this->hasSignatureToken($this->tokens, T_ABSTRACT, T_CLASS);

        // Lets check if there is any inheritance, or if we implement any interfaces
        $this->currentDefinition->extends = trim(
            $this->resolveUsedNamespace(
                $this->currentDefinition,
                $this->getParent($tokens)
            ),
            '\\'
        );
        // Get all the interfaces we have
        $this->currentDefinition->implements = $this->getInterfaces($this->currentDefinition);

        // Get all class constants
        $this->currentDefinition->constants = $this->getConstants($tokens);

        // Only thing still missing are the methods, so ramp up our FunctionParser
        $functionParser = new FunctionParser(
            $this->file,
            $this->structureDefinitionHierarchy,
            $this->structureMap,
            $this->currentDefinition,
            $this->tokens
        );

        $this->currentDefinition->functionDefinitions = $functionParser->getDefinitionListFromTokens(
            $tokens,
            $getRecursive
        );

        // If we have to parse the definition in a recursive manner, we have to get the parent invariants
        if ($getRecursive === true) {

            // Add all the assertions we might get from ancestral dependencies
            $this->addAncestralAssertions($this->currentDefinition);
        }

        // Lets get the attributes the class might have
        $this->currentDefinition->attributeDefinitions = $this->getAttributes(
            $tokens,
            $this->currentDefinition->getInvariants()
        );

        // Lock the definition
        $this->currentDefinition->lock();

        // Before exiting we will add the entry to the current structure definition hierarchy
        $this->structureDefinitionHierarchy->insert($this->currentDefinition);

        return $this->currentDefinition;
    }

    /**
     * This method will add all assertions any ancestral structures (parent classes, implemented interfaces) might have
     * to the passed class definition.
     *
     * @param \TechDivision\PBC\Entities\Definitions\ClassDefinition $classDefinition The class definition we have to
     *                                                                                add the assertions to
     *
     * @return null
     */
    protected function addAncestralAssertions(ClassDefinition $classDefinition)
    {
        $dependencies = $classDefinition->getDependencies();
        foreach ($dependencies as $dependency) {

            // freshly set the dependency definition to avoid side effects
            $dependencyDefinition = null;

            $fileEntry = $this->structureMap->getEntry($dependency);
            if (!$fileEntry instanceof Structure) {

                // Continue, don't fail as we might have dependencies which are not under PBC surveillance
                continue;
            }

            // Get the needed parser
            $structureParserFactory = new StructureParserFactory();
            $parser = $structureParserFactory->getInstance(
                $fileEntry->getType(),
                $fileEntry->getPath(),
                $this->structureMap,
                $this->structureDefinitionHierarchy
            );

            // Get the definition
            $dependencyDefinition = $parser->getDefinition(
                $dependency,
                true
            );

            // Only classes and traits have invariants
            if ($fileEntry->getType() === 'class') {

                $classDefinition->ancestralInvariants = $dependencyDefinition->getInvariants(true);
            }

            // Finally add the dependency definition to our structure definition hierarchy to avoid
            // redundant parsing
            $this->structureDefinitionHierarchy->insert($dependencyDefinition);
        }
    }

    /**
     * Will search for the name of this class
     *
     * @param array $tokens Array of tokens for this class
     *
     * @return string
     */
    protected function getName(array $tokens)
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
     * Will find the parent class we have (if any). Will return an empty string if there is none.
     *
     * @param array $tokens Array of tokens for this class
     *
     * @return string
     */
    protected function getParent(array $tokens)
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
     * Will return an array containing all interfaces this class implements
     *
     * @param ClassDefinition &$classDefinition Reference of class definition so we can resolve the namespaces
     *
     * @return array
     */
    protected function getInterfaces(ClassDefinition & $classDefinition)
    {
        // Check the tokens
        $interfaces = array();
        for ($i = 0; $i < $this->tokenCount; $i++) {

            // If we got the class name
            if ($this->tokens[$i][0] === T_IMPLEMENTS) {

                for ($j = $i + 1; $j < $this->tokenCount; $j++) {

                    if ($this->tokens[$j] === '{' || $this->tokens[$j][0] === T_CURLY_OPEN ||
                        $this->tokens[$j][0] === T_EXTENDS
                    ) {

                        return $interfaces;

                    } elseif ($this->tokens[$j][0] === T_STRING) {

                        $interfaces[] = $this->resolveUsedNamespace(
                            $classDefinition,
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
     * To retrieve the different properties of an attribute it relies on getAttributeProperties().
     * We need the list of invariants to mark attributes wo are under surveillance.
     *
     * @param array         $tokens     Array of tokens for this class
     * @param TypedListList $invariants List of invariants so we can compare the attributes to
     *
     * @return AttributeDefinitionList
     */
    protected function getAttributes(array $tokens, TypedListList $invariants = null)
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
            $listIterator = $invariants->getIterator();
            $listCount = $listIterator->count();
            $attributeIterator = $attributes->getIterator();
            $attributeCount = $attributeIterator->count();
            for ($i = 0; $i < $attributeCount; $i++) {

                // Do we have any of these attributes in our invariants?
                $listIterator = $invariants->getIterator();
                for ($j = 0; $j < $listCount; $j++) {

                    // Did we get anything useful?
                    if ($listIterator->current() === null) {

                        continue;
                    }
                    $invariantIterator = $listIterator->current()->getIterator();
                    $invariantCount = $invariantIterator->count();
                    for ($k = 0; $k < $invariantCount; $k++) {

                        $attributePosition = strpos(
                            $invariantIterator->current()->getString(),
                            '$this->' . ltrim(
                                $attributeIterator->current()->name,
                                '$'
                            )
                        );

                        if ($attributePosition !== false
                        ) {

                            // Tell them we were mentioned and persist it
                            $attributeIterator->current()->inInvariant = true;
                        }

                        $invariantIterator->next();
                    }
                    $listIterator->next();
                }
                $attributeIterator->next();
            }
        }

        return $attributes;
    }

    /**
     * Will return a definition of an attribute as far as we can extract it from the token array
     *
     * @param array $tokens            Array of tokens for this class
     * @param int   $attributePosition Position of the attribute within the token array
     *
     * @return AttributeDefinition
     */
    protected function getAttributeProperties(array $tokens, $attributePosition)
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
