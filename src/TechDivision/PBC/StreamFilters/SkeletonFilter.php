<?php
/**
 * TechDivision\PBC\StreamFilters\SkeletonFilter
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 */

namespace TechDivision\PBC\StreamFilters;

use TechDivision\PBC\Entities\Definitions\FunctionDefinition;
use TechDivision\PBC\Exceptions\GeneratorException;

/**
 * @package     TechDivision\PBC
 * @subpackage  StreamFilters
 * @copyright   Copyright (c) 2013 <info@techdivision.com> - TechDivision GmbH
 * @license     http://opensource.org/licenses/osl-3.0.php
 *              Open Software License (OSL 3.0)
 * @author      Bernhard Wick <b.wick@techdivision.com>
 */
class SkeletonFilter extends AbstractFilter
{
    /**
     * @const   int
     */
    const FILTER_ORDER = 0;

    /**
     * @var FunctionDefinitionList
     */
    public $params;

    /**
     * @return int
     */
    public function getFilterOrder()
    {
        return self::FILTER_ORDER;
    }

    /**
     * We do not have any dependencies here. So we will always return true.
     *
     * @return bool
     */
    public function dependenciesMet()
    {
        return true;
    }

    /**
     * @param $in
     * @param $out
     * @param $consumed
     * @param $closing
     * @return int|void
     * @throws \TechDivision\PBC\Exceptions\GeneratorException
     */
    public function filter($in, $out, &$consumed, $closing)
    {
        $path = $this->params[1];
        $functionDefinitions = $this->params[0];
        // Get our buckets from the stream
        $functionHook = '';
        $firstIteration = true;
        while ($bucket = stream_bucket_make_writeable($in)) {

            // Lets cave in the original filepath and the modification time
            if ($firstIteration === true) {

                $bucket->data = str_replace(
                    '<?php',
                    '<?php /* ' . PBC_ORIGINAL_PATH_HINT . $path . '#' . filemtime(
                        $path
                    ) . PBC_ORIGINAL_PATH_HINT . ' */',
                    $bucket->data
                );
                $firstIteration = false;
            }

            // Get the tokens
            $tokens = token_get_all($bucket->data);

            // Go through the tokens and check what we found
            $tokensCount = count($tokens);
            for ($i = 0; $i < $tokensCount; $i++) {

                // We need something to hook into, right after class header seems fine
                if (is_array($tokens[$i]) && $tokens[$i][0] === T_CLASS) {

                    for ($j = $i; $j < $tokensCount; $j++) {

                        if (is_array($tokens[$j])) {

                            $functionHook .= $tokens[$j][1];

                        } else {

                            $functionHook .= $tokens[$j];
                        }

                        // If we got the opening bracket we can break
                        if ($tokens[$j] === '{') {

                            break;
                        }
                    }

                    // If the function hook is empty we failed and should stop what we are doing
                    if (empty($functionHook)) {

                        throw new GeneratorException();
                    }

                    // Get the code for our contract depth attribute
                    // We do this here, as this point is only passed once!
                    $depthCode = $this->generateDepthCode();

                    // Insert the code
                    // We also have to insert our contract depth attribute
                    $bucket->data = str_replace($functionHook, $functionHook . $depthCode, $bucket->data);
                }

                // Did we find a function? If so check if we know that thing and insert the code of its preconditions.
                if (is_array($tokens[$i]) && $tokens[$i][0] === T_FUNCTION) {

                    // Get the name of the function
                    $functionName = $tokens[$i + 2][1];

                    // Get the hook for the "original marker"
                    $markerHook = $tokens[$i][1] . $tokens[$i + 1][1] . $tokens[$i + 2][1];

                    // Check if we got the function in our list, if not continue
                    $functionDefinition = $functionDefinitions->get($functionName);
                    if (!$functionDefinition instanceof FunctionDefinition) {

                        continue;

                    } else {

                        // Change the function name to indicate this is the original function
                        $bucket->data = preg_replace(
                            '/' . $markerHook . ' *\(/',
                            $markerHook . PBC_ORIGINAL_FUNCTION_SUFFIX . '(',
                            $bucket->data
                        );

                        // Get the code for the assertions
                        $functionCode = $this->generateFunctionCode($functionDefinition);

                        // Insert the code
                        $bucket->data = str_replace($functionHook, $functionHook . $functionCode, $bucket->data);

                        // "Destroy" the function definition to avoid reusing it in the next loop iteration
                        $functionDefinition = null;
                    }
                }
            }

            // Tell them how much we already processed, and stuff it back into the output
            $consumed += $bucket->datalen;
            stream_bucket_append($out, $bucket);
        }

        return PSFS_PASS_ON;
    }

    /**
     * @return string
     */
    private function generateDepthCode()
    {
        $code = '
        /**
         *  @var int
         */
         private $' . PBC_CONTRACT_DEPTH . ' = 0;';

        return $code;
    }

    /**
     * @param   FunctionDefinition $functionDefinition
     * @return  string
     */
    private function generateFunctionCode(FunctionDefinition $functionDefinition)
    {
        // Build up the header
        $code = $functionDefinition->getHeader('definition');

        // No just place all the placeholder for other filters to come
        $code .= '{';

        // Invariant is not needed in private functions
        if ($functionDefinition->visibility !== 'private') {

            $code .= PBC_INVARIANT_PLACEHOLDER . 'entry' . PBC_PLACEHOLDER_CLOSE .
                '$this->' . PBC_CONTRACT_DEPTH . '++;';
        }

        $code .= PBC_PRECONDITION_PLACEHOLDER . $functionDefinition->name . PBC_PLACEHOLDER_CLOSE .
            PBC_OLD_SETUP_PLACEHOLDER . $functionDefinition->name . PBC_PLACEHOLDER_CLOSE;

        // Build up the call to the original function.
        // We use self:: instead of $this-> to not call the child implementation by accident!
        $code .= PBC_KEYWORD_RESULT . ' = self::' . $functionDefinition->getHeader('call', true) . ';';

        // No just place all the other placeholder for other filters to come
        $code .= PBC_POSTCONDITION_PLACEHOLDER . $functionDefinition->name . PBC_PLACEHOLDER_CLOSE;

        // Invariant is not needed in private functions
        if ($functionDefinition->visibility !== 'private') {

            $code .= PBC_INVARIANT_PLACEHOLDER . 'exit' . PBC_PLACEHOLDER_CLOSE .
                '$this->' . PBC_CONTRACT_DEPTH . '--;';
        }

        $code .= 'return ' . PBC_KEYWORD_RESULT . ';}';

        return $code;
    }
}