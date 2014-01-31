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
use TechDivision\PBC\Entities\Lists\FunctionDefinitionList;
use TechDivision\PBC\Exceptions\GeneratorException;
use TechDivision\PBC\Utils\Formatting;

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
     * @var array
     */
    protected $neededActions = array('injectMagicConstants' => 1, 'injectOriginalPathHint' => 1);

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
     *
     * @return int|void
     * @throws \TechDivision\PBC\Exceptions\GeneratorException
     */
    public function filter($in, $out, &$consumed, $closing)
    {
        $path = $this->params->path;
        $functionDefinitions = $this->params->functionDefinitions;
        // Get our buckets from the stream
        $functionHook = '';
        $firstIteration = true;
        while ($bucket = stream_bucket_make_writeable($in)) {

            // Lets cave in the original filepath and the modification time
            if ($firstIteration === true) {

                $this->injectOriginalPathHint($bucket->data, $path);

                $firstIteration = false;
            }

            // Get the tokens
            $tokens = token_get_all($bucket->data);

            // Go through the tokens and check what we found
            $tokensCount = count($tokens);
            for ($i = 0; $i < $tokensCount; $i++) {

                // Has to be done only once at the beginning of the definition
                if (empty($functionHook)) {

                    // We need something to hook into, right after class header seems fine
                    if (is_array($tokens[$i]) && $tokens[$i][0] === T_CLASS) {

                        for ($j = $i; $j < $tokensCount; $j++) {

                            if (is_array($tokens[$j])) {

                                $functionHook .= $tokens[$j][1];
                            } else {

                                $functionHook .= $tokens[$j];
                            }

                            // If we got the opening bracket we can break
                            if ($tokens[$j] === '{' || $tokens[$j][0] === T_CURLY_OPEN) {

                                break;
                            }
                        }

                        // If the function hook is empty we failed and should stop what we are doing
                        if (empty($functionHook)) {

                            throw new GeneratorException();
                        }

                        // Insert the placeholder for our function hook.
                        // All following injects into the structure body will rely on it
                        $bucket->data = str_replace(
                            $functionHook,
                            $functionHook . PBC_FUNCTION_HOOK_PLACEHOLDER . PBC_PLACEHOLDER_CLOSE,
                            $bucket->data
                        );
                        $functionHook = PBC_FUNCTION_HOOK_PLACEHOLDER . PBC_PLACEHOLDER_CLOSE;
                    }

                    // We have to create the local constants which will substitute __DIR__ and __FILE__
                    // within the cache folder.
                    $this->injectMagicConstants($bucket->data, $path);

                }
                // Did we find a function? If so check if we know that thing and insert the code of its preconditions.
                if (is_array($tokens[$i]) && $tokens[$i][0] === T_FUNCTION && @$tokens[$i + 2][0] === T_STRING) {

                    // Get the name of the function
                    $functionName = $tokens[$i + 2][1];

                    // Check if we got the function in our list, if not continue
                    $functionDefinition = $functionDefinitions->get($functionName);
                    if (!$functionDefinition instanceof FunctionDefinition ||
                        $functionDefinition->isAbstract === true
                    ) {

                        continue;
                    }

                    // Lets inject the needed condition checks as a pseudo around advice
                    $tmp = $this->injectFunctionCode($bucket->data, $tokens, $i, $functionDefinition);

                    // Were we able to inject into the definition? If not we have to fail here
                    if (!$tmp) {

                        throw new GeneratorException('Not able to inject condition code for ' . $functionName);
                    }

                    // "Destroy" the function definition to avoid reusing it in the next loop iteration
                    $functionDefinition = null;
                }
            }

            // We have to substitute magic __DIR__ and __FILE__ constants
            $this->substituteMagicConstants($bucket->data);

            // Tell them how much we already processed, and stuff it back into the output
            $consumed += $bucket->datalen;
            stream_bucket_append($out, $bucket);
        }

        return PSFS_PASS_ON;
    }

    /**
     * Will inject condition checking code in front and behind the functions body.
     *
     * @param string             $bucketData         Reference for the current bucket data
     * @param array              $tokens             The tokens for the current bucket data
     * @param int                $indexStart         The index of the token array at which we found the function head
     * @param FunctionDefinition $functionDefinition The function definition object
     *
     * @return bool
     */
    protected function injectFunctionCode(
        & $bucketData,
        array $tokens,
        $indexStart,
        FunctionDefinition $functionDefinition
    ) {
        // Go through the tokens and check what we found.
        // We will collect the complete function head including the function's opening {
        $tokensCount = count($tokens);
        $tmp = '';
        for ($i = $indexStart; $i < $tokensCount; $i++) {

            if (is_array($tokens[$i])) {

                $tmp .= $tokens[$i][1];

            } else {

                $tmp .= $tokens[$i];
            }

            // If we got the bracket opening the function body we can exit the loop
            if ($tokens[$i] === '{' || $tokens[$i][0] === T_CURLY_OPEN) {

                break;
            }
        }

        // Get the position of the function header within the bucket data
        $beforeIndexIndicator = strpos($bucketData, $tmp);

        // Did we find something? If not we will fail here
        if ($beforeIndexIndicator === false) {

            return false;
        }

        // Our index for injection the $beforeCode code part has to be at the end of our produced method head
        $beforeIndex = $beforeIndexIndicator + strlen($tmp);

        // __get and __set need some special steps so we can inject our own logic into them
        $injectNeeded = false;
        if ($functionDefinition->name === '__get' || $functionDefinition->name === '__set') {

            $injectNeeded = true;
        }

        // Get the code used before the original body
        $beforeCode = $this->generateBeforeCode($injectNeeded, $functionDefinition);

        // Get the code used after the original body
        $afterCode = $this->generateAfterCode($injectNeeded, $functionDefinition);

        // The index where we will inject the $afterCode code parts
        $afterIndex = $beforeIndex + strlen($functionDefinition->body);

        // Do our indeces make sense? $beforeIndex has to be before (smaller) than $afterIndex (suprise!)
        if ($afterIndex < $beforeIndex) {

            return false;
        }

        // As the injection of the $afterCode code parts occur AFTER $beforeIndex, we can save some work by doing it in
        // the reverse order.
        // If one of them fails we are screwed anyway
        $bucketData = substr_replace($bucketData, $afterCode, $afterIndex, 0);
        $bucketData = substr_replace($bucketData, $beforeCode, $beforeIndex, 0);

        // If we are still here we seem to have succeeded
        return true;
    }

    /**
     * Will generate the code used before the original function body
     *
     * @param bool  $injectNeeded Determine if we have to use a try...catch block
     * @param FunctionDefinition $functionDefinition The function definition object
     *
     * @return null
     */
    protected function generateBeforeCode($injectNeeded, FunctionDefinition $functionDefinition)
    {
        $code = PBC_CONTRACT_CONTEXT . ' = \TechDivision\PBC\ContractContext::open();';

        // Invariant is not needed in private or static functions.
        // Also make sure that there is none in front of the constructor check
        if ($functionDefinition->visibility !== 'private' &&
            !$functionDefinition->isStatic && $functionDefinition->name !== '__construct'
        ) {

            $code .= PBC_INVARIANT_PLACEHOLDER . PBC_PLACEHOLDER_CLOSE;
        }

        $code .= PBC_PRECONDITION_PLACEHOLDER . $functionDefinition->name . PBC_PLACEHOLDER_CLOSE .
            PBC_OLD_SETUP_PLACEHOLDER . $functionDefinition->name . PBC_PLACEHOLDER_CLOSE;

        // Build up the original function as a closure
        $code .= PBC_CLOSURE_VARIABLE . ' = ' . $functionDefinition->getHeader('closure') . '{';

        return $code;
    }

    /**
     * Will generate the code used after the original function body
     *
     * @param bool  $injectNeeded Determine if we have to use a try...catch block
     * @param FunctionDefinition $functionDefinition The function definition object
     *
     * @return null
     */
    protected function generateAfterCode($injectNeeded, FunctionDefinition $functionDefinition)
    {
        $code = '};';

        // If we inject something we might need a try ... catch around the original call.
        if ($injectNeeded === true) {

            $code .= 'try {';
        }

        // Build up the call to the original function.
        $code .= PBC_KEYWORD_RESULT . ' = ' . PBC_CLOSURE_VARIABLE . '();';

        // Finish the try ... catch and place the inject marker
        if ($injectNeeded === true) {

            $code .= '} catch (\Exception $e) {}' . PBC_METHOD_INJECT_PLACEHOLDER .
                $functionDefinition->name . PBC_PLACEHOLDER_CLOSE;
        }

        // No just place all the other placeholder for other filters to come
        $code .= PBC_POSTCONDITION_PLACEHOLDER . $functionDefinition->name . PBC_PLACEHOLDER_CLOSE;

        // Invariant is not needed in private or static functions
        if ($functionDefinition->visibility !== 'private' && !$functionDefinition->isStatic) {

            $code .= PBC_INVARIANT_PLACEHOLDER . PBC_PLACEHOLDER_CLOSE;
        }

        $code .= 'if (' . PBC_CONTRACT_CONTEXT . ') {\TechDivision\PBC\ContractContext::close();}
            return ' . PBC_KEYWORD_RESULT . ';';

        return $code;
    }

    /**
     * Will substitute all magic __DIR__ and __FILE__ constants with our prepared substitutes to
     * emulate original original filesystem context when in cache folder.
     *
     * @param $bucketData
     *
     * @return bool
     */
    protected function substituteMagicConstants(& $bucketData)
    {
        // Inject the code
        $bucketData = str_replace(
            array('__DIR__', '__FILE__'),
            array('self::' . PBC_DIR_SUBSTITUTE, 'self::' . PBC_FILE_SUBSTITUTE),
            $bucketData
        );

        // Still here? Success then.
        return true;
    }

    /**
     * Will inject the code to declare our local constants PBC_FILE_SUBSTITUTE and PBC_DIR_SUBSTITUTE
     * which are used for substitution of __FILE__ and __DIR__.
     *
     * @param        $bucketData
     * @param string $file
     *
     * @return bool
     */
    protected function injectOriginalPathHint(& $bucketData, $file)
    {
        // Do need to do this?
        if ($this->neededActions[__FUNCTION__] <= 0 || strpos($bucketData, '<?php') === false) {

            return false;
        }

        // Build up the needed code for our hint
        $code = ' /* ' . PBC_ORIGINAL_PATH_HINT . $file . '#' .
            filemtime(
                $file
            ) . PBC_ORIGINAL_PATH_HINT . ' */';

        // Inject the code
        $index = strpos($bucketData, '<?php');
        $bucketData = substr_replace($bucketData, $code, $index + 5, 0);

        // Still here? Success then.
        $this->neededActions[__FUNCTION__]--;

        return true;
    }

    /**
     * Will inject the code to declare our local constants PBC_FILE_SUBSTITUTE and PBC_DIR_SUBSTITUTE
     * which are used for substitution of __FILE__ and __DIR__.
     *
     * @param        $bucketData
     * @param string $file
     *
     * @return bool
     */
    protected function injectMagicConstants(& $bucketData, $file)
    {
        $dir = dirname($file);
        $functionHook = PBC_FUNCTION_HOOK_PLACEHOLDER . PBC_PLACEHOLDER_CLOSE;

        if ($this->neededActions[__FUNCTION__] <= 0 || strpos($bucketData, $functionHook) === false) {

            return false;
        }

        // Build up the needed code for __DIR__ substitution
        $code = '/**
             * @const   string
             */
            const ' . PBC_DIR_SUBSTITUTE . ' = "' . $dir . '";';

        // Build up the needed code for __FILE__ substitution
        $code .= '/**
             * @const   string
             */
            const ' . PBC_FILE_SUBSTITUTE . ' = "' . $file . '";';

        // Inject the code
        $bucketData = str_replace($functionHook, $functionHook . $code, $bucketData);

        // Still here? Success then.
        $this->neededActions[__FUNCTION__]--;

        return true;
    }
}
