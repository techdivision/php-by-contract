<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 16.07.13
 * Time: 11:34
 * To change this template use File | Settings | File Templates.
 */

namespace TechDivision\PBC\Parser;

use TechDivision\PBC\Entities\Definitions\ParameterDefinition;
use TechDivision\PBC\Entities\Lists\FunctionDefinitionList;
use TechDivision\PBC\Entities\Definitions\FunctionDefinition;
use TechDivision\PBC\Entities\Lists\ParameterDefinitionList;

/**
 * Function FunctionParser
 */
class FunctionParser extends AbstractParser
{
    /**
     * @param $tokens
     * @return bool|FunctionDefinitionList
     */
    public function getDefinitionListFromTokens(array $tokens)
    {
        // First of all we need to get the function tokens
        $tokens = $this->getFunctionTokens($tokens);

        // Did we get something valuable?
        $functionDefinitionList = new FunctionDefinitionList();
        if ($tokens === false) {

            return false;

        } elseif (count($tokens) === 1) {
            // We got what we came for, or did we?

            if (isset($tokens[0])) {

                $functionDefinitionList->add($this->getDefinitionFromTokens($tokens[0]));
            }

            return $functionDefinitionList;

        } elseif (count($tokens) > 1) {
            // We are still here, but got a function name to look for

            foreach ($tokens as $token) {

                try {

                    $functionDefinitionList->add($this->getDefinitionFromTokens($token));

                } catch (\UnexpectedValueException $e) {
                    // Just try the next one

                    continue;
                }
            }
        }

        return $functionDefinitionList;
    }

    /**
     * Returns a FunctionDefinition from a token array.
     *
     * This method will use a set of other methods to parse a token array and retrieve any
     * possible information from it. This information will be entered into a FunctionDefinition object.
     *
     * @access private
     * @param $tokens
     * @return FunctionDefinition
     */
    private function getDefinitionFromTokens(array $tokens)
    {
        // First of all we need a new FunctionDefinition to fill
        $functionDefinition = new FunctionDefinition();

        // For our next step we would like to get the doc comment (if any)
        $functionDefinition->docBlock = $this->getDocBlock($tokens, T_FUNCTION);

        // Get the function signature
        $functionDefinition->isFinal = $this->hasSignatureToken($tokens, T_FINAL, T_FUNCTION);
        $functionDefinition->visibility = $this->getFunctionVisibility($tokens);
        $functionDefinition->isStatic = $this->hasSignatureToken($tokens, T_STATIC, T_FUNCTION);
        $functionDefinition->name = $this->getFunctionName($tokens);

        // Lets also get out parameters
        $functionDefinition->parameterDefinitions = $this->getParameterDefinitionList($tokens);

        // So we got our docBlock, now we can parse the precondition annotations from it
        $annotationParser = new AnnotationParser();
        $functionDefinition->preconditions = $annotationParser->getConditions($functionDefinition->docBlock, PBC_KEYWORD_PRE);

        // Does this method require the use of our "old" mechanism?
        $functionDefinition->usesOld = $this->usesKeyword($functionDefinition->docBlock, PBC_KEYWORD_OLD);

        // We have to get the body of the function, so we can recreate it
        $functionDefinition->body = $this->getFunctionBody($tokens);

        // So we got our docBlock, now we can parse the postcondition annotations from it
        $functionDefinition->postconditions = $annotationParser->getConditions($functionDefinition->docBlock, PBC_KEYWORD_POST);

        return $functionDefinition;
    }

    /**
     * @param array $tokens
     * @return ParameterDefinitionList
     */
    private function getParameterDefinitionList(array $tokens)
    {
        // Check the tokens
        $parameterString = '';
        $parameterDefinitionList = new ParameterDefinitionList();
        for ($i = 0; $i < count($tokens); $i++) {

            // If we got the function definition, no scan everything from the first ( to the next )
            if ($tokens[$i][0] === T_FUNCTION) {

                $bracketPassed = null;
                for ($j = $i; $j < count($tokens); $j++) {

                    // If we got the function definition, no scan everything from the first ( to the closing )
                    if ($tokens[$j] === '(') {

                        if ($bracketPassed === null) {

                            $bracketPassed = 0;
                            // We do not want to get this token as well.
                            continue;

                        } else {

                            $bracketPassed ++;
                        }
                    }

                    // We got A closing bracket, decrease the counter
                    if ($tokens[$j] === ')') {

                        $bracketPassed --;
                    }

                    if ($bracketPassed >= 0 && $bracketPassed !== null) {

                        // Collect what we get
                        if (is_array($tokens[$j])) {

                            $parameterString .= $tokens[$j][1];

                        } else {

                            $parameterString .= $tokens[$j];
                        }
                    } elseif ($bracketPassed !== null) {
                        // If we got the closing bracket we can leave both loops

                        break 2;
                    }
                }
            }
        }

        // Now lets analyse what we got
        $parameterStrings = explode(',', $parameterString);
        foreach ($parameterStrings as $key => $param) {

            if ($this->getBracketCount($param, '(') > 0) {

                $param = $param . ', ' . $parameterStrings[$key + 1];
                unset($parameterStrings[$key + 1]);
            }

            $param = trim($param);
            $paramPieces = explode('$', $param);

            // Get a new ParameterDefinition
            $parameterDefinition = new ParameterDefinition();

            // we either get one or two pieces
            if (count($paramPieces) === 1) {

                continue;

            } elseif (count($paramPieces) === 2) {

                $parameterDefinition->type = trim($paramPieces[0]);

                // We might have an overload going on
                $nameArray = explode('=', $paramPieces[1]);
                $parameterDefinition->name = '$' . trim($nameArray[0]);

                // check if we got a default value for overloading
                if (isset($nameArray[1])) {

                    $parameterDefinition->defaultValue = $nameArray[1];
                }
            }

            // Add the definition to the list
            $parameterDefinitionList->add($parameterDefinition);
        }

        return $parameterDefinitionList;
    }

    /**
     * @param $tokens
     * @return string
     */
    private function getFunctionName(array $tokens)
    {
        // Check the tokens
        $functionName = '';
        for ($i = 0; $i < count($tokens); $i++) {

            // If we got the function name
            if ($tokens[$i][0] === T_FUNCTION && $tokens[$i + 2][0] === T_STRING) {

                $functionName = $tokens[$i + 2][1];
            }
        }

        // Return what we did or did not found
        return $functionName;
    }

    /**
     * @param $tokens
     * @return string
     */
    private function getFunctionBody(array $tokens)
    {
        // We will iterate over the token array and collect everything from the first opening curly bracket until the last
        $functionBody = '';
        for ($i = 0; $i < count($tokens); $i++) {

            // If we passed the function token
            if ($tokens[$i][0] === T_FUNCTION) {

                $passedFunction = true;
            }

            // If we got the curly bracket that opens the function
            if ($tokens[$i] === '{' && $passedFunction === true) {

                // Get everything until we reach the closing bracket
                $bracketCounter = 1;
                for ($j = $i + 1; $j < count($tokens); $j++) {

                    // We have to count brackets. When they are even again we will break.
                    if ($tokens[$j] === '{') {

                        $bracketCounter++;

                    } elseif ($tokens[$j] === '}') {

                        $bracketCounter--;
                    }

                    // Do we have an even amount of brackets yet?
                    if ($bracketCounter === 0) {

                        return $functionBody;
                    }

                    // Collect what we get
                    if (is_array($tokens[$j])) {

                        $functionBody .= $tokens[$j][1];

                    } else {

                        $functionBody .= $tokens[$j];
                    }
                }
            }
        }

        // Return what we did or did not found
        return $functionBody;
    }

    /**
     * @param $tokens
     * @return array|bool
     *s
     */
    private function getFunctionTokens(array $tokens)
    {
        // Iterate over all the tokens and filter the different function portions out
        $result = array();
        for ($i = 0; $i < count($tokens); $i++) {

            // If we got a function keyword, we have to check how far the function extends,
            // then copy the array within that bounds, but first of all we have to check if we got
            // a function name.
            // Otherwise anonymous functions will make us go crazy.
            if (is_array($tokens[$i]) && $tokens[$i][0] === T_FUNCTION &&
                !empty($tokens[$i + 2]) && $tokens[$i + 2] !== '(') {

                // The lower bound should be the last semicolon|closing curly bracket|PHP tag before the function
                $lowerBound = 0;
                for ($j = $i - 1; $j >= 0; $j--) {

                    if ($tokens[$j] === ';' || $tokens[$j] === '{' ||
                        (is_array($tokens[$j]) && $tokens[$j][0] === T_OPEN_TAG)
                    ) {

                        $lowerBound = $j + 1;
                        break;
                    }
                }

                // The upper bound should be the first time the curly brackets are even again or the first occurrence
                // of the semicolon. The semicolon is important, as we have to get function declarations in interfaces
                // as well.
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

                    // Did we reach a semicolon before reaching a opening curly bracket?
                    if ($bracketCounter === null && $tokens[$j] === ';') {

                        $upperBound = $j + 1;
                        break;
                    }

                    // Do we have an even amount of brackets yet?
                    if ($bracketCounter === 0) {

                        $upperBound = $j + 1;
                        break;
                    }
                }

                $result[] = array_slice($tokens, $lowerBound, $upperBound - $lowerBound);
            }
        }

        return $result;
    }

    /**
     * @param array $tokens
     * @return string
     */
    private function getFunctionVisibility(array $tokens)
    {
        // Check out all the tokens and look if we find the right thing. We can do that as these keywords are not valid
        // within a function definition.
        $visibility = '';
        for ($i = 0; $i < count($tokens); $i++) {

            // Search for the visibility
            if (is_array($tokens[$i]) && ($tokens[$i][0] === T_PRIVATE || $tokens[$i][0] === T_PROTECTED)) {

                // Got it!
                $visibility = $tokens[$i][1];
            }

            // Did we reach the function already?
            if ($tokens[$i][0] === T_FUNCTION) {

                break;
            }
        }

        // Last but not least we have to check if got the visibility, if not, set it public.
        // This is necessary, as missing visibility in the definition will also default to public
        if ($visibility === '') {

            $visibility = 'public';
        }

        return $visibility;
    }
}