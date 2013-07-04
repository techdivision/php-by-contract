<?php

/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 19.06.13
 * Time: 15:15
 * To change this template use File | Settings | File Templates.
 */

use TechDivision\PBC\Entities\Assertion;
use TechDivision\PBC\Entities\AssertionList;
use TechDivision\PBC\Entities\ClassDefinition;
use TechDivision\PBC\Entities\FunctionDefinition;
use TechDivision\PBC\Entities\FunctionDefinitionList;
use TechDivision\PBC\Entities\ScriptDefinition;

/**
 * Class TestAnnotationParser
 */
class TestAnnotationParser
{
    /**
     * @pre $param1 < 27
     *
     * @param integer $param1
     * @param string $param2
     * @param Exception $param3
     *
     * @post string $result
     *
     * @return string
     */
    function testFest1($param1, $param2, Exception $param3)
    {
        return (string) $param1 . $param2 . $param3->getMessage();
    }

    /**
     * @pre $param1 == "null"
     *
     * @param string $param1
     *
     * @post array $result
     *
     * @return array
     */
    function testFest2($param1)
    {
        return array($param1);
    }

    /**
     * @param $fileName
     */
    private function parseFile($fileName)
    {
        $fileContent = file_get_contents($fileName);

        $docBlocks = array();
        preg_match_all('/\/\*\*(.+?)\(/s', $fileContent, $docBlocks);

        // We have to check what kind of file we are scanning. Might be a class or just a script or might be even
        // both in one file.
        // If we do not get a class name, we assume a simple script.
        $className = $this->getClassName($fileName);

        // Did we find a class?
        $definition = null;
        if (empty($className) === false) {

            // We got a class, hoooray!
            $definition = new ClassDefinition();
            $definition->name = $className;
            $definition->namespace = $this->getClassNamespace($fileName);

            // As we are handling a class right now we might check for invariant annotations as well
            $definition->invariantConditions = $this->getConditions($docBlocks[0], PBC_KEYWORD_INVARIANT);

        } else {

            // We seem to have gotten a simple script file, duh!
            $definition = new ScriptDefinition();
            $definition->name = $fileName;
        }

        // The filePath attribute is needed by whatever we got
        $definition->filePath = fullpath($fileName);

        // Get all the functions/methods
        $functionList = new FunctionDefinitionList();
        foreach ($docBlocks[1] as $docBlock) {

            // Create our definition of this function/method
            $functionDefinition = new FunctionDefinition();

            // We should get a name
            $functionDefinition->name = $this->parseFunctionName($docBlock);

            // Lets check if we use any of our self defined keywords.
            $functionDefinition->usesOld = $this->usesKeyword($docBlock, PBC_KEYWORD_OLD);
            $functionDefinition->usesResult = $this->usesKeyword($docBlock, PBC_KEYWORD_RESULT);

            // Lets get our pre- and postconditions
            $functionDefinition->preConditions = $this->getConditions($docBlock, PBC_KEYWORD_PRE);
            $functionDefinition->postConditions = $this->getConditions($docBlock, PBC_KEYWORD_POST);

            // Add the definition to the list
            $functionList->offsetSet($functionDefinition->name, $functionDefinition);

            // Clean the variable as it might be reused
            $functionDefinition = null;
        }

        // Add the list of function definitions to our overall definition
        $definition->functionDefinitions = $functionList;

        return $definition;
    }

    /**
     * @param $docBlock
     *
     * @return mixed
     */
    private function getConditions($docBlock, $conditionKeyword)
    {
        // There are only 2 valid condition types
        if ($conditionKeyword !== PBC_KEYWORD_PRE && $conditionKeyword !== PBC_KEYWORD_POST
            && $conditionKeyword !== PBC_KEYWORD_INVARIANT) {

            return false;
        }

        // Get our conditions
        $rawConditions = array();
        if ($conditionKeyword === PBC_KEYWORD_POST) {

            preg_match_all('/' . $conditionKeyword . '.+?\n|' . '@return' . '.+?\n/s', $docBlock, $rawConditions);
        } elseif ($conditionKeyword === PBC_KEYWORD_PRE) {

            preg_match_all('/' . $conditionKeyword . '.+?\n|' . '@param' . '.+?\n/s', $docBlock, $rawConditions);

        } else {

            preg_match_all('/' . $conditionKeyword . '.+?\n/s', $docBlock, $rawConditions);
        }

        // Lets build up the result array
        $result = new AssertionList();
        foreach ($rawConditions as $condition) {

            $result->offsetSet(null, $this->parseAssertion($condition));
        }

        return $result;
    }

    /**
     * @param $docString
     */
    private function parseAssertion($docString)
    {
        // We have to differ between several types of assertions, so lets check which one we got
        $annotations = array('@param', '@return', PBC_KEYWORD_POST, PBC_KEYWORD_PRE);

        $usedAnnotation = '';
        foreach ($annotations as $annotation) {

            if (str_pos($docString, $annotation) !== false) {

                $usedAnnotation = $annotation;
                break;
            }
        }

        // Handle the used annotation types differently
        $assertion = new Assertion();
        switch ($usedAnnotation) {
            // We got something which can only contain type information
            case '@param':
            case '@return':

                $explodedDocString = explode(' ', preg_replace('/\s+|\t+/', ' ', $docString));

                // The first operand should be clear now
                $assertion->firstOperand = $explodedDocString[2];

                // For the operator we either have an is_x or an is_a function.
                // This also determines which secondOperator we have
                if (function_exists('is_' . $explodedDocString[1]) === true) {

                    $assertion->operator = 'is_' . $explodedDocString[1];
                    // There is no second operand needed
                    $assertion->secondOperand = null;

                } elseif (class_exists($explodedDocString[1]) === true) {

                    $assertion->operator = 'is_a';
                    $assertion->secondOperand = $explodedDocString[1];
                }
                break;

            // We got our own definitions. Could be a bit more complex here
            case PBC_KEYWORD_POST:
            case PBC_KEYWORD_POST:

                $explodedDocString = explode(' ', preg_replace('/\s+|\t+/', ' ', $docString));

                // By rules of the syntax of an assertion {P} A {Q} the operator must be in the middle
                $assertion->operator = $explodedDocString[2];

                // First and second operand can be taken by the schema {P} A {Q} as well
                $assertion->firstOperand = $explodedDocString[1];
                $assertion->secondOperand = $explodedDocString[3];

                break;

            default:

                return false;
                break;
        }

        return $assertion;
    }

    /**
     *
     */
    private function parseFunctionName($docBlock)
    {
        // Check for matches of a standard function name
        $matches = array();
        $success = (boolean)preg_match('/function(.*)/s', $docBlock, $matches);

        // Did we find anything?
        if ($success === false) {

            return false;
        }

        // Trim before return
        return trim($matches[1]);
    }

    /**
     * @param $docBlock
     * @param $keyword
     *
     * @return bool
     */
    private function usesKeyword($docBlock, $keyword)
    {
        if (str_pos($docBlock, $keyword) === false) {

            return false;
        } else {

            return true;
        }
    }

    /**
     * @param $fileName
     *
     * @return string
     */
    private function getClassNamespace($fileName)
    {
        // Lets open the file readonly
        $fileResource = fopen($fileName, 'r');

        // Prepare some variables we will need
        $namespace = '';
        $buffer = '';

        // Declaring the iterator here, to not check the start of the file again and again
        $i = 0;
        while (isset($namespace) === false) {

            // Is the file over already?
            if (feof($fileResource)) {

                break;
            }

            // We only read a small portion of the file, as we should find the namespace declaration up front
            $buffer .= fread($fileResource, 512);
            // Get all the tokens in the read buffer
            $tokens = token_get_all($buffer);

            // If we did not reach anything of value yet we will continue reading
            if (strpos($buffer, '{') === false) {

                continue;
            }

            // Check the tokens
            for (; $i < count($tokens); $i++) {

                // If we got the namespace
                if ($tokens[$i][0] === T_NAMESPACE) {

                    for ($j = $i + 1; $j < count($tokens); $j++) {

                        if ($tokens[$j][0] === T_STRING) {

                            $namespace .= '\\' . $tokens[$j][1];

                        } elseif ($tokens[$j] === '{' || $tokens[$j] === ';') {

                            break;
                        }
                    }
                }
            }
        }

        // Return what we did or did not found
        return $namespace;
    }

    /**
     * @param $fileName
     *
     * @return string
     */
    private function getClassName($fileName)
    {
        // Lets open the file readonly
        $fileResource = fopen($fileName, 'r');

        // Prepare some variables we will need
        $className = '';
        $buffer = '';

        // Declaring the iterator here, to not check the start of the file again and again
        $i = 0;
        while (isset($className) === false) {

            // Is the file over already?
            if (feof($fileResource)) {

                break;
            }

            // We only read a small portion of the file, as we should find the class declaration up front
            $buffer .= fread($fileResource, 512);
            // Get all the tokens in the read buffer
            $tokens = token_get_all($buffer);

            // If we did not reach anything of value yet we will continue reading
            if (strpos($buffer, '{') === false) {

                continue;
            }

            // Check the tokens
            for (; $i < count($tokens); $i++) {

                // If we got the class name
                if ($tokens[$i][0] === T_CLASS) {

                    for ($j = $i + 1; $j < count($tokens); $j++) {

                        if ($tokens[$j] === '{') {

                            $className = $tokens[$i + 2][1];
                        }
                    }
                }
            }
        }

        // Return what we did or did not found
        return $className;
    }
}