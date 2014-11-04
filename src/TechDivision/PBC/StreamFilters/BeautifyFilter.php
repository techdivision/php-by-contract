<?php
/**
 * File containing the BeautifyFilter class
 *
 * PHP version 5
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage StreamFilters
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */

namespace TechDivision\PBC\StreamFilters;

/**
 * TechDivision\PBC\StreamFilters\BeautifyFilter
 *
 * This filter will buffer the input stream, check it for php syntax errors and beautify it using
 * the nikic/php-parser lib
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage StreamFilters
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */
class BeautifyFilter extends AbstractFilter
{

    /**
     * @const integer FILTER_ORDER Order number if filters are used as a stack, higher means below others
     */
    const FILTER_ORDER = 99;

    /**
     * The main filter method.
     * Implemented according to \php_user_filter class. Will loop over all stream buckets, buffer them and perform
     * the needed actions.
     *
     * @param resource $in       Incoming bucket brigade we need to filter
     * @param resource $out      Outgoing bucket brigade with already filtered content
     * @param integer  $consumed The count of altered characters as buckets pass the filter
     * @param boolean  $closing  Is the stream about to close?
     *
     * @throws \Exception
     * @throws \PHPParser_Error
     *
     * @return integer
     *
     * @link http://www.php.net/manual/en/php-user-filter.filter.php
     *
     * TODO The buffering does not work that well, maybe we should implement universal buffering within parent class!
     */
    public function filter($in, $out, &$consumed, $closing)
    {
        // Get our buckets from the stream
        $buffer = '';
        while ($bucket = stream_bucket_make_writeable($in)) {

            $buffer .= $bucket->data;

            // Tell them how much we already processed, and stuff it back into the output
            $consumed += $bucket->datalen;

            // Save a bucket for later reuse
            $bigBucket = $bucket;
        }

        // Beautify all the buckets!
        $parser = new \PHPParser_Parser(new \PHPParser_Lexer);
        $prettyPrinter = new \PHPParser_PrettyPrinter_Default;

        try {
            // parse
            $stmts = $parser->parse($buffer);

            $data = '<?php ' . $prettyPrinter->prettyPrint($stmts);

        } catch (PHPParser_Error $e) {

            throw $e;
        }

        // Refill the bucket with the beautified data
        // Do not forget to set the length!
        $bigBucket->data = $data;
        $bigBucket->datalen = strlen($data);

        // Only append our big bucket
        stream_bucket_append($out, $bigBucket);

        return PSFS_PASS_ON;
    }
}
