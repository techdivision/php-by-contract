<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 19.06.13
 * Time: 15:15
 * To change this template use File | Settings | File Templates.
 */

namespace TechDivision\PBC\Parser;

use TechDivision\PBC\Entities\Definitions\AttributeDefinition;
use TechDivision\PBC\Entities\Lists\AssertionList;
use TechDivision\PBC\Entities\Definitions\ClassDefinition;
use TechDivision\PBC\Entities\Definitions\FunctionDefinition;
use TechDivision\PBC\Entities\Lists\AttributeDefinitionList;
use TechDivision\PBC\Entities\Lists\FunctionDefinitionList;
use TechDivision\PBC\Config;

/**
 * Class AnnotationParser
 */
class AnnotationParser extends AbstractParser
{
    /**
     * @var
     */
    private $config;

    /**
     * @var array
     */
    private $validSimpleTypes = array(
        'array',
        'bool',
        'callable',
        'double',
        'float',
        'int',
        'integer',
        'long',
        'null',
        'numeric',
        'object',
        'real',
        'resource',
        'scalar',
        'string'
    );

    public function __construct()
    {
        $config = new Config();
        $this->config = $config->getConfig('Parser');
    }

    /**
     * @param $docBlock
     * @param $conditionKeyword
     * @return bool|AssertionList
     */
    public function getConditions($docBlock, $conditionKeyword)
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

                    $result->add($assertion);
                }
            }
        }

        return $result;
    }

    /**
     * @param $docString
     * @return bool
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
            $stringPiece = trim($stringPiece);
            if (strpos($stringPiece, '$') === 0 || $stringPiece === PBC_KEYWORD_RESULT || $stringPiece === PBC_KEYWORD_OLD) {

                return $stringPiece;
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
        $validTypes = array_flip($this->validSimpleTypes);
        foreach ($explodedString as $stringPiece) {

            // Check if we got a variable
            $stringPiece = strtolower(trim($stringPiece));
            if (isset($validTypes[$stringPiece])) {

                return $stringPiece;
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

        // Check if we got a valid docsting, if so the first part must begin with @
        if (strpos($explodedString[0], '@') !== 0) {

            return false;
        }

        // We assume we got a class if the second part is no scalar type and no variable
        $validTypes = array_flip($this->validSimpleTypes);
        $stringPiece = trim($explodedString[1]);
        if (strpos($stringPiece, '$') === false && !isset($validTypes[strtolower($stringPiece)])) {

            // If we got "void" we do not need to bother
            if ($stringPiece !== 'void') {

                return $stringPiece;
            }
        }

        // We found nothing; tell them.
        return false;
    }

    /**
     * @param $docString
     * @param $operand
     * @return array|bool
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
}