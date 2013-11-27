<?php
/**
 * TechDivision\PBC\StreamFilters\PostconditionFilter
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
use TechDivision\PBC\Entities\Lists\TypedListList;

/**
 * @package     TechDivision\PBC
 * @subpackage  StreamFilters
 * @copyright   Copyright (c) 2013 <info@techdivision.com> - TechDivision GmbH
 * @license     http://opensource.org/licenses/osl-3.0.php
 *              Open Software License (OSL 3.0)
 * @author      Bernhard Wick <b.wick@techdivision.com>
 */
class PostconditionFilter extends AbstractFilter
{
    /**
     * @const   int
     */
    const FILTER_ORDER = 2;

    /**
     * @var array
     */
    private $dependencies = array('SkeletonFilter');

    /**
     * @var FunctionDefinitionList
     */
    public $params;

    /**
     * @return array
     */
    public function getDependencies()
    {
        return $this->dependencies;
    }

    /**
     * @return int
     */
    public function getFilterOrder()
    {
        return self::FILTER_ORDER;
    }

    /**
     * @throws \Exception
     */
    public function dependenciesMet()
    {
        throw new \Exception();
    }

    /**
     * @param $in
     * @param $out
     * @param $consumed
     * @param $closing
     * @return int|void
     */
    public function filter($in, $out, &$consumed, $closing)
    {
        // Get our buckets from the stream
        while ($bucket = stream_bucket_make_writeable($in)) {

            // Get the tokens
            $tokens = token_get_all($bucket->data);

            // Go through the tokens and check what we found
            $tokensCount = count($tokens);
            for ($i = 0; $i < $tokensCount; $i++) {

                // Did we find a function? If so check if we know that thing and insert the code of its preconditions.
                if (is_array($tokens[$i]) && $tokens[$i][0] === T_FUNCTION && is_array($tokens[$i + 2])) {

                    // Get the name of the function
                    $functionName = $tokens[$i + 2][1];

                    // Check if we got the function in our list, if not continue
                    $functionDefinition = $this->params->get($functionName);

                    if (!$functionDefinition instanceof FunctionDefinition) {

                        continue;

                    } else {

                        // If we use the old notation we have to insert the statement to make a copy
                        $bucket->data = str_replace(PBC_OLD_SETUP_PLACEHOLDER . $functionDefinition->name .
                            PBC_PLACEHOLDER_CLOSE, $this->generateOldCode(), $bucket->data);

                        // Get the code for the assertions
                        $code = $this->generateCode($functionDefinition->getPostconditions());

                        // Insert the code
                        $bucket->data = str_replace(PBC_POSTCONDITION_PLACEHOLDER . $functionDefinition->name .
                            PBC_PLACEHOLDER_CLOSE, $code, $bucket->data);

                        // "Destroy" code and function definition
                        $code = null;
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
     * Will return code to create an entry for the old object state.
     *
     * @return string
     */
    private function generateOldCode()
    {
        return PBC_KEYWORD_OLD . ' = clone $this;';
    }

    /**
     * @param TypedListList $assertionLists
     * @return string
     */
    private function generateCode(TypedListList $assertionLists)
    {
        // We only use contracting if we're not inside another contract already
        $code = '/* BEGIN OF POSTCONDITION ENFORCEMENT */
        if ($this->' . PBC_CONTRACT_DEPTH . ' < 2) {';

        // We need a counter to check how much conditions we got
        $conditionCounter = 0;
        $listIterator = $assertionLists->getIterator();
        for ($i = 0; $i < $listIterator->count(); $i++) {

            // Create the inner loop for the different assertions
            $assertionIterator = $listIterator->current()->getIterator();

            // Only act if we got actual entries
            if ($assertionIterator->count() === 0) {

                // increment the outer loop
                $listIterator->next();
                continue;
            }

            $codeFragment = array();
            for ($j = 0; $j < $assertionIterator->count(); $j++) {

                $codeFragment[] = $assertionIterator->current()->getString();

                // Forward the iterator and tell them we got a condition
                $assertionIterator->next();
                $conditionCounter++;
            }

            // Lets insert the condition check (if there have been any)
            if (!empty($codeFragment)) {

                $code .= 'if (!((' . implode(') && (', $codeFragment) . '))){' .
                    PBC_FAILURE_VARIABLE . ' = \'(' . str_replace('\'', '"', implode(') && (', $codeFragment)) . ')\';' .
                    PBC_PROCESSING_PLACEHOLDER . 'postcondition' . PBC_PLACEHOLDER_CLOSE . '}';
            }

            // increment the outer loop
            $listIterator->next();
        }

        // Closing bracket for contract depth check
        $code .= '}' .
            '/* END OF POSTCONDITION ENFORCEMENT */';

        // Did we get anything at all? If not only give back a comment.
        if ($conditionCounter === 0) {

            $code = '/* No postconditions for this function/method */';
        }

        return $code;
    }
} 