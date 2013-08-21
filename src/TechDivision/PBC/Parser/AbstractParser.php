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
     * Will return true if the token is found, and false if not or an error occured.
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

}