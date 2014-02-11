<?php
/**
 * File containing the AbstractParser class
 *
 * PHP version 5
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Parser
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 *             Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */

namespace TechDivision\PBC\Parser;

use TechDivision\PBC\Interfaces\ParserInterface;
use TechDivision\PBC\StructureMap;
use TechDivision\PBC\Entities\Definitions\StructureDefinitionHierarchy;

/**
 * TechDivision\PBC\Parser\AbstractParser
 *
 * The abstract class AbstractParser which provides a basic implementation other parsers can inherit from
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Parser
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 *             Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */
abstract class AbstractParser implements ParserInterface
{
    /**
     * Does a certain block of code contain a certain keyword
     *
     * @param string $docBlock The code block to search in
     * @param string $keyword  The keyword to search for
     *
     * @return boolean
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
     * Will return the length of the string a token array is based on.
     *
     * @param array $tokens The token array
     *
     * @return integer
     */
    protected function getStringLength($tokens)
    {
        // Iterator over the tokens and get their lengt
        $result = 0;
        $tokenCount = count($tokens);
        for ($i = 0; $i < $tokenCount; $i++) {

            if (is_array($tokens[$i])) {

                $result += strlen($tokens[$i][1]);

            } else {

                $result += strlen($tokens[$i]);
            }
        }

        return $result;
    }

    /**
     * Will search for a certain token in a certain entity.
     *
     * This method will search the signature of either a class or a function for a certain token e.g. final.
     * Will return true if the token is found, and false if not or an error occurred.
     *
     * @param array   $tokens        The token array to search in
     * @param integer $searchedToken The token we search for, use PHP tokens here
     * @param integer $parsedEntity  The type of entity we search in front of, use PHP tokens here
     *
     * @return boolean
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
     * Will get the count of brackets (round or curly) within a string.
     * Will return an integer which is calculated as the number of opening brackets against closing ones.
     * Will return false if the bracket type is not recognized
     *
     * @param string $string  The string to search in
     * @param string $bracket Type of bracket we have. Might be (, ), { or }
     *
     * @return boolean|integer
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
     * Will return the DocBlock of a certain entity.
     *
     * @param array   $tokens         The token array to search in
     * @param integer $structureToken The type of entity we search in front of, use PHP tokens here
     *
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
