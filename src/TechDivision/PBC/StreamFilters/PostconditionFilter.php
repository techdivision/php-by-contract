<?php
/**
 * File containing the PostconditionFilter class
 *
 * PHP version 5
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage StreamFilters
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 *             Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */

namespace TechDivision\PBC\StreamFilters;

use TechDivision\PBC\Entities\Definitions\FunctionDefinition;
use TechDivision\PBC\Entities\Lists\FunctionDefinitionList;
use TechDivision\PBC\Entities\Lists\TypedListList;
use TechDivision\PBC\Exceptions\GeneratorException;

/**
 * TechDivision\PBC\StreamFilters\PostconditionFilter
 *
 * This filter will buffer the input stream and add all postcondition related information at prepared locations
 * (see $dependencies)
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage StreamFilters
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 *             Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */
class PostconditionFilter extends AbstractFilter
{
    /**
     * @const integer FILTER_ORDER Order number if filters are used as a stack, higher means below others
     */
    const FILTER_ORDER = 2;

    /**
     * @var array $dependencies Other filters on which we depend
     */
    private $dependencies = array('SkeletonFilter');

    /**
     * @var mixed $params The parameter(s) we get passed when appending the filter to a stream
     * @link http://www.php.net/manual/en/class.php-user-filter.php
     */
    public $params;

    /**
     * Will return the dependency array
     *
     * @return array
     */
    public function getDependencies()
    {
        return $this->dependencies;
    }

    /**
     * Will return the order number the concrete filter has been constantly assigned
     *
     * @return integer
     */
    public function getFilterOrder()
    {
        return self::FILTER_ORDER;
    }

    /**
     * Not implemented yet
     *
     * @throws \Exception
     *
     * @return void
     */
    public function dependenciesMet()
    {
        throw new \Exception();
    }

    /**
     * The main filter method.
     * Implemented according to \php_user_filter class. Will loop over all stream buckets, buffer them and perform
     * the needed actions.
     *
     * @param resource $in        Incoming bucket brigade we need to filter
     * @param resource $out       Outgoing bucket brigade with already filtered content
     * @param integer  &$consumed The count of altered characters as buckets pass the filter
     * @param boolean  $closing   Is the stream about to close?
     *
     * @throws \TechDivision\PBC\Exceptions\GeneratorException
     *
     * @return integer
     *
     * @link http://www.php.net/manual/en/php-user-filter.filter.php
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
                        $this->injectOldCode($bucket->data, $functionDefinition);

                        // Get the code for the assertions
                        $code = $this->generateCode($functionDefinition->getAllPostconditions());

                        // Insert the code
                        $bucket->data = str_replace(
                            PBC_POSTCONDITION_PLACEHOLDER . $functionDefinition->getName() .
                            PBC_PLACEHOLDER_CLOSE,
                            $code,
                            $bucket->data
                        );

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
     * Will change code to create an entry for the old object state.
     *
     * @param string                                                    &$bucketData         Payload of the currently
     *                                                                                       filtered bucket
     * @param \TechDivision\PBC\Entities\Definitions\FunctionDefinition &$functionDefinition Currently handled function
     *
     * @throws \TechDivision\PBC\Exceptions\GeneratorException
     *
     * @return boolean
     */
    private function injectOldCode(& $bucketData, FunctionDefinition & $functionDefinition)
    {
        // Do we even need to do anything?
        if ($functionDefinition->getUsesOld() !== true) {

            return false;
        }
        // If the function is static it should not use the pbcOld keyword as there is no state to the class!
        if ($functionDefinition->getIsStatic() === true) {

            throw new GeneratorException('Cannot clone class state in static method ' . $functionDefinition->getName());
        }

        // Still here? Then inject the clone statement to preserve an instance of the object prior to our call.
        $bucketData = str_replace(
            PBC_OLD_SETUP_PLACEHOLDER . $functionDefinition->getName() .
            PBC_PLACEHOLDER_CLOSE,
            PBC_KEYWORD_OLD . ' = clone $this;',
            $bucketData
        );

        // Still here? We encountered no error then.
        return true;
    }

    /**
     * Will generate the code needed to enforce made postcondition assertions
     *
     * @param \TechDivision\PBC\Entities\Lists\TypedListList $assertionLists List of assertion lists
     *
     * @return string
     */
    private function generateCode(TypedListList $assertionLists)
    {
        // We only use contracting if we're not inside another contract already
        $code = '/* BEGIN OF POSTCONDITION ENFORCEMENT */
        if (' . PBC_CONTRACT_CONTEXT . ') {';

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
                    PBC_FAILURE_VARIABLE . ' = \'(' . str_replace(
                        '\'',
                        '"',
                        implode(') && (', $codeFragment)
                    ) . ')\';' .
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
