<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 19.06.13
 * Time: 15:15
 * To change this template use File | Settings | File Templates.
 */

namespace TechDivision\PBC\Parser;

require_once __DIR__ . "/../Entities/Lists/AssertionList.php";
require_once __DIR__ . "/../Entities/Definitions/ClassDefinition.php";
require_once __DIR__ . "/../Entities/Definitions/FunctionDefinition.php";
require_once __DIR__ . "/../Entities/Lists/FunctionDefinitionList.php";
require_once __DIR__ . "/../Entities/Assertions/BasicAssertion.php";
require_once __DIR__ . "/../Entities/Assertions/InstanceAssertion.php";
require_once __DIR__ . "/../Entities/Assertions/TypeAssertion.php";

use TechDivision\PBC\Entities\Lists\AssertionList;
use TechDivision\PBC\Entities\Definitions\ClassDefinition;
use TechDivision\PBC\Entities\Definitions\FunctionDefinition;
use TechDivision\PBC\Entities\Lists\FunctionDefinitionList;
use TechDivision\PBC\Entities\Definitions\MetaDefinition;
use TechDivision\PBC\Entities\Definitions\ScriptDefinition;

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

                    } elseif ($tokens[$i - 2][0] === T_PRIVATE || $tokens[$i - 2][0] === T_PROTECTED || $tokens[$i - 2][0] === T_PUBLIC) {

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
        //TODO We still miss the attributes!!!!

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

                        $classDefinition->docBlock = $tokens[$i - 2][1];

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

            // Check if we need @return as well
            if ($this->config['enforceDefaultTypeSafety'] === true) {

                $regex = '/' . str_replace('\\', '\\\\', $conditionKeyword) . '.+?\n|' . '@return' . '.+?\n/s';

            } else {

                $regex = '/' . str_replace('\\', '\\\\', $conditionKeyword) . '.+?\n';
            }

            preg_match_all($regex, $docBlock, $rawConditions);

        } elseif ($conditionKeyword === PBC_KEYWORD_PRE) {

            // Check if we need @return as well
            if ($this->config['enforceDefaultTypeSafety'] === true) {

                $regex = '/' . str_replace('\\', '\\\\', $conditionKeyword) . '.+?\n|' . '@param' . '.+?\n/s';

            } else {

                $regex = '/' . str_replace('\\', '\\\\', $conditionKeyword) . '.+?\n';
            }

            preg_match_all($regex, $docBlock, $rawConditions);

        } else {

            preg_match_all('/' . str_replace('\\', '\\\\', $conditionKeyword) . '.+?\n/s', $docBlock, $rawConditions);
        }

        // Lets build up the result array
        $result = new AssertionList();
        if (empty($rawConditions) === false) {
            foreach ($rawConditions[0] as $condition) {

                $assertion = $this->parseAssertion($condition);
                if ($assertion !== false) {

                    $result->offsetSet(null, $assertion);
                }
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

        $variable = $this->filterVariable($docString);
        $type = $this->filterType($docString);
        $class = $this->filterClass($docString);

        switch ($usedAnnotation) {
            // We got something which can only contain type information
            case '@param':
            case '@return':

                // Now we have to check what we got
                // First of all handle if we got a simple type
                if ($type !== false) {

                    $assertionType = 'TechDivision\PBC\Entities\Assertions\TypeAssertion';

                } elseif ($class !== false) {

                    $type = $class;
                    $assertionType = 'TechDivision\PBC\Entities\Assertions\InstanceAssertion';

                } else {

                    return false;
                }

                // We handled what kind of assertion we need, now check what we will assert
                if ($variable !== false) {

                    $assertion = new $assertionType($variable, $type);

                } elseif ($usedAnnotation === '@return') {

                    $assertion = new $assertionType(PBC_KEYWORD_RESULT, $type);

                } else {

                    return false;
                }

                break;

            // We got our own definitions. Could be a bit more complex here
            case PBC_KEYWORD_PRE:
            case PBC_KEYWORD_POST:
            case PBC_KEYWORD_INVARIANT:

                $operand = $this->filterOperand($docString);
                $operators = $this->filterOperators($docString, $operand);
            //var_dump($variable, $type, $class, $operand, $operators, '----------------------------------');
                // Now we have to check what we got
                // First of all handle if we got a simple type
                if ($type !== false) {

                    $assertionType = 'TechDivision\PBC\Entities\Assertions\TypeAssertion';

                } elseif ($class !== false) {

                    $assertionType = 'TechDivision\PBC\Entities\Assertions\InstanceAssertion';

                } elseif ($operand !== false && $operators !== false) {

                    $assertionType = 'TechDivision\PBC\Entities\Assertions\BasicAssertion';

                } else {

                    return false;
                }

                // We handled what kind of assertion we need, now check what we will assert
                if ($assertionType === 'TechDivision\PBC\Entities\Assertions\BasicAssertion') {

                    $assertion = new $assertionType($operators[0], $operators[1], $operand);

                } else {

                    if ($variable !== false) {

                        $assertion = new $assertionType($variable, $type);

                    } elseif ($usedAnnotation === '@return') {

                        $assertion = new $assertionType(PBC_KEYWORD_RESULT, $type);

                    } else {

                        return false;
                    }

                }
                break;

            default:

                return false;
                break;
        }

        return $assertion;
    }

    /**
     * @param $docString
     *
     * @return bool
     */
    private function filterVariable($docString)
    {
        // Explode the string to get the different pieces
        $explodedString = explode(' ', $docString);

        // Filter for the first variable. The first as there might be a variable name in any following description
        foreach ($explodedString as $stringPiece) {

            // Check if we got a variable
            if (strpos($stringPiece, '$') === 0 || $stringPiece === PBC_KEYWORD_RESULT || $stringPiece === PBC_KEYWORD_OLD) {

                return trim($stringPiece);
            }
        }

        // We found nothing; tell them.
        return false;
    }

    /**
     * @param $docString
     *
     * @return bool
     */
    private function filterType($docString)
    {
        // Explode the string to get the different pieces
        $explodedString = explode(' ', $docString);

        // Filter for the first variable. The first as there might be a variable name in any following description
        foreach ($explodedString as $stringPiece) {

            // Check if we got a variable
            if (function_exists('is_' . $stringPiece) && $stringPiece !== 'a') {

                return trim($stringPiece);
            }
        }

        // We found nothing; tell them.
        return false;
    }

    /**
     * @param $docString
     *
     * @return bool
     */
    private function filterClass($docString)
    {
        // Explode the string to get the different pieces
        $explodedString = explode(' ', $docString);

        // Filter for the first variable. The first as there might be a variable name in any following description
        foreach ($explodedString as $stringPiece) {

            // Check if we got a variable
            if (class_exists($stringPiece) || interface_exists($stringPiece)) {

                return trim($stringPiece);
            }
        }

        // We found nothing; tell them.
        return false;
    }

    /**
     * @param $docString
     *
     * @return bool
     */
    private function filterOperators($docString, $operand)
    {
        // To savely get everything we will trust in the PHP tokens
        $tokens = token_get_all('<?php ' . $docString);

        for ($i = 0; $i < count($tokens); $i++) {

            if (is_array($tokens[$i]) && $tokens[$i][1] === $operand && is_array($tokens[$i - 2]) && is_array($tokens[$i + 2])) {

                // There is a special case, as we could use $this
                if ($tokens[$i - 4][1] === '$this') {

                    return array(trim('$this->' . $tokens[$i - 2][1]), trim($tokens[$i + 2][1]));

                } else {

                    return array(trim($tokens[$i - 2][1]), trim($tokens[$i + 2][1]));
                }

            } else if ($tokens[$i] === $operand && is_array($tokens[$i - 2]) && is_array($tokens[$i + 2])) {

                // There is a special case, as we could use $this
                if ($tokens[$i - 4][1] === '$this') {

                    return array(trim('$this->' . $tokens[$i - 2][1]), trim($tokens[$i + 2][1]));

                } else {

                    return array(trim($tokens[$i - 2][1]), trim($tokens[$i + 2][1]));
                }
            }
        }

        // We found nothing; tell them.
        return false;
    }

    /**
     * @param $docString
     *
     * @return bool
     */
    private function filterOperand($docString)
    {
        $validOperands = array(
            '==' => '!=',
            '===' => '!==',
            '<>' => '==',
            '<' => '>=',
            '>' => '<=',
            '<=' => '>',
            '>=' => '<',
            '!=' => '==',
            '!==' => '==='
        );

        // Explode the string to get the different pieces
        $explodedString = explode(' ', $docString);

        // Filter for the first variable. The first as there might be a variable name in any following description
        foreach ($explodedString as $stringPiece) {

            // Check if we got a valid operand
            if (isset($validOperands[$stringPiece])) {

                return $stringPiece;
            }
        }

        // We found nothing; tell them.
        return false;
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