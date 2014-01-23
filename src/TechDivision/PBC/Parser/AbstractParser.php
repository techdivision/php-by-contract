<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 11.07.13
 * Time: 16:42
 * To change this template use File | Settings | File Templates.
 */

namespace TechDivision\PBC\Parser;

use TechDivision\PBC\Interfaces\ParserInterface;
use TechDivision\PBC\StructureMap;
use TechDivision\PBC\Entities\Definitions\StructureDefinitionHierarchy;

abstract class AbstractParser implements ParserInterface
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
        if ($parsedEntity !== T_FUNCTION && $parsedEntity !== T_CLASS && $parsedEntity !== T_INTERFACE) {

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
     * @param $string
     * @param $bracket
     * @return bool|int
     */
    protected function getBracketCount($string, $bracket)
    {
        $roundBrackets = array_flip(array('(', ')'));
        $curlyBrackets = array_flip(array('{', '}'));

        if (isset($roundBrackets[$bracket])) {

            $openingBracket = '(';
            $closingBracket = ')';

        } elseif (isset($curlyBrackets[$bracket])) {

            $openingBracket = '{';
            $closingBracket = '}';

        } else {

            return false;
        }

        return substr_count($string, $openingBracket) - substr_count($string, $closingBracket);
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

                    if ($tokens[$j][0] === $structureToken) {

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
}