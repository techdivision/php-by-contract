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
use TechDivision\PBC\Entities\Definitions\AttributeDefinition;
use TechDivision\PBC\Entities\Lists\ClassDefinitionList;
use TechDivision\PBC\Entities\Lists\AttributeDefinitionList;

/**
 * Class ClassParser
 */
class ClassParser
{
    /**
     * @param $file
     * @return bool|ClassDefinitionList
     */
    public function getDefinitionListFromFile($file)
    {
        // Get all the token arrays for the different classes
        $tokens = $this->getClassTokens($file);

        // Did we get the right thing?
        if (!is_array($tokens)) {

            return false;
        }

        $classDefinitionList = new ClassDefinitionList();
        foreach ($tokens as $token) {

            try {

                $classDefinitionList->add($this->getDefinitionFromTokens($token));

            } catch (\UnexpectedValueException $e) {
                // Just try the next one

                continue;
            }
        }

        return $classDefinitionList;
    }

    /**
     * @param $file
     * @param null $className
     * @return bool|void
     */
    public function getDefinitionFromFile($file, $className = null)
    {
        // First of all we need to get the class tokens
        $tokens = $this->getClassTokens($file);

        // Did we get something valueable?
        if ($tokens === false) {

            return false;

        } elseif ($className === null && count($tokens) > 1) {
            // If we did not get a class name and we got more than one class we can fail right here
            return false;

        } elseif (count($tokens) === 0) {
            // We got what we came for

            return $this->getDefinitionFromTokens($tokens[0]);

        } elseif (is_string($className) && count($tokens) > 1) {
            // We are still here, but got a class name to look for

            foreach ($tokens as $key => $token) {

                // Now iterate over the array and search for the class we want
                for ($i = 0; $i < count($token); $i++) {

                    if (is_array($token[$i]) && $token[$i] === T_CLASS && $token[$i + 2] === $className) {

                        return $this->getDefinitionFromTokens($tokens[$key]);
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
     * @return ClassDefinition
     */
    private function getDefinitionFromTokens($tokens)
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

        // Lets get the attributes the class might have
        $classDefinition->attributeDefinitions = $this->getAttributes($tokens);

        // Only thing still missing are the methods, so ramp up our FunctionParser
        $functionParser = new FunctionParser();
        $classDefinition->functionDefinitions = $functionParser->getDefinitionListFromTokens($tokens);

        return $classDefinition;
    }

    /**
     * @param $tokens
     * @return string
     */
    private function getClassName($tokens)
    {
        // Check the tokens
        $className = '';
        for ($i = 0; $i < count($tokens); $i++) {

            // If we got the class name
            if ($tokens[$i][0] === T_CLASS) {

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
     * @return bool
     *
     * TODO inherit from AbstractParser
     */
    private function isFinalClass($tokens)
    {
        // Check the tokens
        for ($i = 0; $i < count($tokens); $i++) {

            // If we got the class name we have to check if we have the final keyword in front of it.
            // I would say should be within 6 tokens in front of the class keyword.
            if ($tokens[$i][0] === T_CLASS) {

                for ($j = $i - 1; $j >= $i - 6; $j--) {

                    if ($tokens[$j][0] === T_FINAL) {

                        return true;
                    }
                }

                // We passed the 6 token loop but did not find something. So report it.
                return false;
            }
        }

        // We are still here? That should not be.
        return false;
    }

    /**
     * @param $tokens
     * @return bool
     *
     * TODO inherit from AbstractParser
     */
    private function isAbstractClass($tokens)
    {
        // Check the tokens
        for ($i = 0; $i < count($tokens); $i++) {

            // If we got the class name we have to check if we have the final keyword in front of it.
            // I would say should be within 6 tokens in front of the class keyword.
            if ($tokens[$i][0] === T_CLASS) {

                for ($j = $i - 1; $j >= $i - 6; $j--) {

                    if ($tokens[$j][0] === T_ABSTRACT) {

                        return true;
                    }
                }

                // We passed the 6 token loop but did not find something. So report it.
                return false;
            }
        }

        // We are still here? That should not be.
        return false;
    }

    /**
     * @param $tokens
     * @return string
     *
     * TODO inherit from AbstractParser
     */
    private function getDocBlock($tokens)
    {
        // The general assumption is: if there is a doc block
        // before the class definition, and the class header follows after it within 6 tokens, then it
        // is the comment block for this class.
        $docBlock = '';
        $passedClass = false;
        for ($i = 0; $i < count($tokens); $i++) {

            // If we passed the class token
            if ($tokens[$i][0] === T_CLASS) {

                $passedClass = true;
            }

            // If we got the docblock without passing the class before
            if ($tokens[$i][0] === T_DOC_COMMENT && $passedClass === false) {

                // Check if we are in front of a class definition
                for ($j = $i + 1; $j < $i + 8; $j++) {

                    if ($tokens[$j][0] === T_CLASS) {

                        $docBlock = $tokens[$i][1];
                        break;
                    }
                }

                // Still here?
                break;
            }
        }

        // Return what we did or did not found
        return $docBlock;
    }

    /**
     * @param $file
     * @return array|bool
     *
     * TODO inherit from AbstractParser
     */
    private function getClassTokens($file)
    {
        // If the file is not readable we have lost
        if (!is_readable($file)) {

            return false;
        }

        // Get all tokens at first
        $tokens = token_get_all(file_get_contents($file));

        // Now iterate over the array and filter different classes from it
        $result = array();
        for ($i = 0; $i < count($tokens); $i++) {

            // If we got a class keyword, we have to check how far the class extends,
            // then copy the array withing that bounds
            if (is_array($tokens[$i]) && $tokens[$i][0] === T_CLASS) {

                // The lower bound should be the last semicolon|closing curly bracket|PHP tag before the class
                $lowerBound = 0;
                for ($j = $i - 1; $j >= 0; $j--) {

                    if ($tokens[$j] === ';' || $tokens[$j] === '}' ||
                        is_array($tokens[$j]) && $tokens[$j][0] === T_OPEN_TAG
                    ) {

                        $lowerBound = $j;
                        break;
                    }
                }

                // The upper bound should be the first time the curly brackets are even again
                $upperBound = count($tokens) - 1;
                $bracketCounter = null;
                for ($j = $i + 1; $j < count($tokens); $j++) {

                    if ($tokens[$j] === '{') {

                        // If we still got null set to 0
                        if ($bracketCounter === null) {

                            $bracketCounter = 0;
                        }

                        $bracketCounter++;

                    } elseif ($tokens[$j] === '}') {

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

                $result[] = array_slice($tokens, $lowerBound, $upperBound - $lowerBound);
            }
        }

        // Last line of defence; did we get something?
        if (empty($result)) {

            return false;
        }

        return $result;
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
     * @return AttributeDefinitionList
     */
    private function getAttributes(array $tokens)
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

                            if ($tokens[$k] === '{') {

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