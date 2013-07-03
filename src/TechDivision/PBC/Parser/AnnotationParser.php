<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 19.06.13
 * Time: 15:15
 * To change this template use File | Settings | File Templates.
 */

namespace TechDivision\PBC\Parser;

require_once __DIR__ . "/../Entities/Assertion.php";
require_once __DIR__ . "/../Entities/AssertionList.php";
require_once __DIR__ . "/../Entities/ClassDefinition.php";
require_once __DIR__ . "/../Entities/FunctionDefinition.php";
require_once __DIR__ . "/../Entities/FunctionDefinitionList.php";
require_once __DIR__ . "/../Entities/ScriptDefinition.php";

use TechDivision\PBC\Entities\Assertion;
use TechDivision\PBC\Entities\AssertionList;
use TechDivision\PBC\Entities\ClassDefinition;
use TechDivision\PBC\Entities\FunctionDefinition;
use TechDivision\PBC\Entities\FunctionDefinitionList;
use TechDivision\PBC\Entities\MetaDefinition;
use TechDivision\PBC\Entities\ScriptDefinition;

/**
 * Class AnnotationParser
 */
class AnnotationParser
{
    /**
     * @var
     */
    private $config;

    public function __construct()
    {
        $config = new \Config();
        $this->config = $config->getConfig('Parser');
    }

    /**
     * @param $fileName
     */
    public function parseFile($fileName)
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
            $definition->name = basename($fileName);
        }

        // The filePath attribute is needed by whatever we got
        $definition->filePath = realpath($fileName);

        // Get all the functions/methods
        $functionList = new FunctionDefinitionList();

        $tokens = token_get_all($fileContent);
        var_dump($tokens);
        die();
        for ($i = 0; $i < count($tokens); $i++) {

            if (is_array($tokens[$i]) === true) {

                if ($tokens[$i][0] === T_FUNCTION) {

                    // Create our definition of this function/method
                    $functionDefinition = new FunctionDefinition();

                    // We should get a name
                    if ($tokens[$i + 2][0] === T_STRING) {

                        $functionDefinition->name = $tokens[$i + 2][1];
                    }

                    if ($tokens[$i - 2][0] === T_DOC_COMMENT) {

                        // Lets check if we use any of our self defined keywords.
                        $functionDefinition->usesOld = $this->usesKeyword($tokens[$i - 2][1], PBC_KEYWORD_OLD);
                        $functionDefinition->usesResult = $this->usesKeyword($tokens[$i - 2][1], PBC_KEYWORD_RESULT);

                        // Lets get our pre- and postconditions
                        $functionDefinition->preConditions = $this->getConditions($tokens[$i - 2][1], PBC_KEYWORD_PRE);
                        $functionDefinition->postConditions = $this->getConditions($tokens[$i - 2][1], PBC_KEYWORD_POST);
                    }

                    // Add the definition to the list
                    $functionList->offsetSet($functionDefinition->name, $functionDefinition);

                    // Clean the variable as it might be reused
                    $functionDefinition = null;
                }
            }
        }

        // Add the list of function definitions to our overall definition
        $definition->functionDefinitions = $functionList;

        return $definition;
    }

    /**
     *
     */
    public function getFunctionDefinition($tokens, $functionName)
    {
        for ($i = 0; $i < count($tokens); $i++) {

            // Only makes sense to check for arrays
            if (is_array($tokens[$i])) {

                // did we find the function we are looking for?
                if ($tokens[$i][0] === T_FUNCTION && $tokens[$i + 2][1] === $functionName) {

                    // Create our definition of this function/method
                    $functionDefinition = new FunctionDefinition();

                    // We should get a name
                    if ($tokens[$i + 2][0] === T_STRING) {

                        $functionDefinition->name = $tokens[$i + 2][1];
                    }

                    if ($tokens[$i - 2][0] === T_DOC_COMMENT) {

                        $functionDefinition->docBlock = $tokens[$i - 2][1];

                        // There is nothing between the function token and the doc comment, so the function has to be public
                        $functionDefinition->access = 'public';

                        // Lets check if we use any of our self defined keywords.
                        $functionDefinition->usesOld = $this->usesKeyword($tokens[$i - 2][1], PBC_KEYWORD_OLD);
                        $functionDefinition->usesResult = $this->usesKeyword($tokens[$i - 2][1], PBC_KEYWORD_RESULT);

                        // Lets get our pre- and postconditions
                        $functionDefinition->preConditions = $this->getConditions($tokens[$i - 2][1], PBC_KEYWORD_PRE);
                        $functionDefinition->postConditions = $this->getConditions($tokens[$i - 2][1], PBC_KEYWORD_POST);

                    } elseif ($tokens[$i - 2][0] === (T_PRIVATE | T_PROTECTED | T_PUBLIC)) {

                        $functionDefinition->access = $tokens[$i - 2][1];

                        if ($tokens[$i - 4][0] === T_DOC_COMMENT) {

                            $functionDefinition->docBlock = $tokens[$i - 4][1];

                            // Lets check if we use any of our self defined keywords.
                            $functionDefinition->usesOld = $this->usesKeyword($tokens[$i - 4][1], PBC_KEYWORD_OLD);
                            $functionDefinition->usesResult = $this->usesKeyword($tokens[$i - 4][1], PBC_KEYWORD_RESULT);

                            // Lets get our pre- and postconditions
                            $functionDefinition->preConditions = $this->getConditions($tokens[$i - 4][1], PBC_KEYWORD_PRE);
                            $functionDefinition->postConditions = $this->getConditions($tokens[$i - 4][1], PBC_KEYWORD_POST);

                        }

                    }

                    // Check if we got parameters
                    if ($tokens[$i + 3] === '(' && $tokens[$i + 4] !== ')') {

                        // Gotta get them all
                        for ($j = $i + 3; $j < count($tokens); $j++) {

                            if (is_array($tokens[$j]) && $tokens[$j][0] === T_VARIABLE) {

                                $functionDefinition->parameters[] = $tokens[$j][1];

                            } elseif ($tokens[$j] === ')') {

                                break;
                            }
                        }
                    }

                    return $functionDefinition;
                }
            }
        }

        // We did not find anything
        return false;
    }

    /**
     *
     */
    public function getMetaDefinition($tokens, $path)
    {
        // The path is something we already know
        $metaDefinition = new MetaDefinition();
        $metaDefinition->filePath = $path;

        for ($i = 0; $i < count($tokens); $i++) {

            // Only makes sense to check for arrays
            if (is_array($tokens[$i])) {

                // Get all the use statements and include/require statements here.
                // Make sure to only use include/requires which do not load class names.
                // Classes should rather be loaded by our AutorLoader class.
                switch ($tokens[$i][0]) {

                    case T_USE:

                        $metaDefinition->uses[] = $tokens[$i + 2][1];

                        break;

                    case T_INCLUDE:

                        if ($this->getClassName($tokens[$i + 2][1] === '')) {

                            $metaDefinition->includes[] = $tokens[$i + 2][1];
                        }

                        break;

                    case T_INCLUDE_ONCE:

                        if ($this->getClassName($tokens[$i + 2][1] === '')) {

                            $metaDefinition->includeOnces[] = $tokens[$i + 2][1];
                        }

                        break;

                    case T_REQUIRE:

                        if ($this->getClassName($tokens[$i + 2][1] === '')) {

                            $metaDefinition->requires[] = $tokens[$i + 2][1];
                        }

                        break;

                    case T_REQUIRE_ONCE:

                        if ($this->getClassName($tokens[$i + 2][1] === '')) {

                            $metaDefinition->requireOnces[] = $tokens[$i + 2][1];
                        }

                        break;

                    default:

                        break;
                }
            }
        }

        // We did not find anything
        return $metaDefinition;
    }

    /**
     *
     */
    public function getClassDefinition($tokens, $className)
    {
        for ($i = 0; $i < count($tokens); $i++) {

            // Only makes sense to check for arrays
            if (is_array($tokens[$i])) {

                // did we find the function we are looking for?
                if ($tokens[$i][0] === T_CLASS && $tokens[$i + 2][1] === $className) {

                    // Create our definition of this function/method
                    $classDefinition = new ClassDefinition();

                    // We should get a name
                    if ($tokens[$i + 2][0] === T_STRING) {

                        $classDefinition->name = $tokens[$i + 2][1];
                    }

                    if ($tokens[$i - 2][0] === T_DOC_COMMENT) {

                        $classDefinition->docBlock = $tokens[$i -2][1];

                        // Lets get our invariant conditions
                        $classDefinition->invariantConditions = $this->getConditions($tokens[$i - 2][1], PBC_KEYWORD_INVARIANT);
                    }

                    return $classDefinition;
                }
            }
        }

        // We did not find anything
        return false;
    }

    /**
     * @param $docBlock
     *
     * @return mixed
     */
    private function getConditions($docBlock, $conditionKeyword)
    {
        // There are only 3 valid condition types
        if ($conditionKeyword !== PBC_KEYWORD_PRE && $conditionKeyword !== PBC_KEYWORD_POST
            && $conditionKeyword !== PBC_KEYWORD_INVARIANT
        ) {

            return false;
        }

        // Get our conditions
        $rawConditions = array();
        if ($conditionKeyword === PBC_KEYWORD_POST) {

            preg_match_all('/' . str_replace('\\', '\\\\', $conditionKeyword) . '.+?\n|' . '@return' . '.+?\n/s', $docBlock, $rawConditions);

        } elseif ($conditionKeyword === PBC_KEYWORD_PRE) {

            preg_match_all('/' . str_replace('\\', '\\\\', $conditionKeyword) . '.+?\n|' . '@param' . '.+?\n/s', $docBlock, $rawConditions);

        } else {

            preg_match_all('/' . str_replace('\\', '\\\\', $conditionKeyword) . '.+?\n/s', $docBlock, $rawConditions);
        }

        // Lets build up the result array
        $result = new AssertionList();
        if (empty($rawConditions) === false) {
            foreach ($rawConditions[0] as $condition) {

                $result->offsetSet(null, $this->parseAssertion($condition));
            }
        }

        return $result;
    }

    /**
     * @param $docString
     */
    private function parseAssertion($docString)
    {
        // We have to differ between several types of assertions, so lets check which one we got
        $annotations = array('@param', '@return', PBC_KEYWORD_POST, PBC_KEYWORD_PRE, PBC_KEYWORD_INVARIANT);

        $usedAnnotation = '';
        foreach ($annotations as $annotation) {

            if (strpos($docString, $annotation) !== false) {

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
                if ($this->config['enforceDefaultTypeSafety'] === true) {
                    $explodedDocString = explode(' ', preg_replace('/\s+|\t+/', ' ', $docString));

                    // The first operand is different for both options
                    if ($usedAnnotation === '@param') {

                        $assertion->firstOperand = $explodedDocString[2];

                    } else {

                        $assertion->firstOperand = PBC_KEYWORD_RESULT;
                    }

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
                }
                break;

            // We got our own definitions. Could be a bit more complex here
            case PBC_KEYWORD_PRE:
            case PBC_KEYWORD_POST:
            case PBC_KEYWORD_INVARIANT:

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
        if (strpos($docBlock, $keyword) === false) {

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
        while (empty($className) === true) {

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