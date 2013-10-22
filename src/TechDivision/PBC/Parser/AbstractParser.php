<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 11.07.13
 * Time: 16:42
 * To change this template use File | Settings | File Templates.
 */

namespace TechDivision\PBC\Parser;

use TechDivision\PBC\Interfaces\Parser;

abstract class AbstractParser implements Parser
{

    /**
     * @param $docBlock
     * @param $keyword
     *
     * @return bool
     */
    protected function usesKeyword($docBlock, $keyword)
    {
        if (strpos($docBlock, $keyword) === false) {

            return false;
        } else {

            return true;
        }
    }

    /**
     * Will search for a certain token in a certain entity.
     *
     * This method will search the signature of either a class or a function for a certain token e.g. final.
     * Will return true if the token is found, and false if not or an error occurred.
     *
     * @param $tokens
     * @param $searchedToken
     * @param $parsedEntity
     * @return bool
     */
    protected function hasSignatureToken($tokens, $searchedToken, $parsedEntity)
    {
        // We have to check what kind of structure we will check. Class and function are the only valid ones.
        if ($parsedEntity !== T_CLASS && $parsedEntity !== T_FUNCTION) {

            return false;
        }

        // Check the tokens
        for ($i = 0; $i < count($tokens); $i++) {

            // If we got the function name we have to check if we have the final keyword in front of it.
            // I would say should be within 6 tokens in front of the function keyword.
            if ($tokens[$i][0] === $parsedEntity) {

                // Check if our $i is lower than 6, if so we have to avoid getting into a negative range
                if ($i < 6) {

                    $i = 6;
                }

                for ($j = $i - 1; $j >= $i - 6; $j--) {

                    if ($tokens[$j][0] === $searchedToken) {

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
     * @param $structureToken
     * @return string
     */
    protected function getDocBlock($tokens, $structureToken)
    {
        // The general assumption is: if there is a doc block
        // before the class definition, and the class header follows after it within 6 tokens, then it
        // is the comment block for this class.
        $docBlock = '';
        $passedClass = false;
        for ($i = 0; $i < count($tokens); $i++) {

            // If we passed the class token
            if ($tokens[$i][0] === $structureToken) {

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
     * Will check a token array for the occurrence of a certain on (class, interface or trait)
     *
     * @param $tokens
     * @return bool
     */
    protected function getStructureToken($tokens)
    {
        // Check the tokens
        $tokenCount = count($tokens);
        for ($i = 0; $i < $tokenCount; $i++) {

            switch ($tokens[$i][0]) {

                case T_CLASS:

                    return 'class';
                    break;

                case T_INTERFACE:

                    return 'interface';
                    break;

                case T_TRAIT:

                    return 'trait';
                    break;

                default:

                    continue;
                    break;
            }
        }

        // We are still here? That should not be.
        return false;
    }

    /**
     * @param $tokens
     * @return array|string
     */
    protected function getConstants($tokens)
    {
        // Check the tokens
        $constants = array();
        for ($i = 0; $i < count($tokens); $i++) {

            // If we got the class name
            if ($tokens[$i][0] === T_CONST) {

                for ($j = $i + 1; $j < count($tokens); $j++) {

                    if ($tokens[$j] === ';') {

                        break;

                    } elseif ($tokens[$j][0] === T_STRING) {

                        $constants[$tokens[$j][1]] = '';

                        for ($k = $j + 1; $k < count($tokens); $k++) {

                            if ($tokens[$k] === ';') {

                                break;

                            } elseif (is_array($tokens[$k]) && $tokens[$k][0] !== '=') {

                                $constants[$tokens[$j][1]] .= $tokens[$k][1];
                            }
                        }

                        // Now trim what we got
                        $constants[$tokens[$j][1]] = trim($constants[$tokens[$j][1]]);
                    }
                }
            }
        }

        // Return what we did or did not found
        return $constants;
    }

    /**
     * @param $file
     * @param $structureToken
     * @return array|bool
     */
    protected function getStructureTokens($file, $structureToken)
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
            if (is_array($tokens[$i]) && $tokens[$i][0] === $structureToken) {

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
}