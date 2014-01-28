<?php
/**
 * TechDivision\PBC\StreamFilters\BeautifyFilter
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 */

namespace TechDivision\PBC\StreamFilters;

/**
 * @package     TechDivision\PBC
 * @subpackage  StreamFilters
 * @copyright   Copyright (c) 2013 <info@techdivision.com> - TechDivision GmbH
 * @license     http://opensource.org/licenses/osl-3.0.php
 *              Open Software License (OSL 3.0)
 * @author      Bernhard Wick <b.wick@techdivision.com>
 */
class BeautifyFilter extends AbstractFilter
{

    /**
     * @const   int
     */
    const FILTER_ORDER = 99;

    /**
     * @var mixed
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
     * We got no dependencies here.
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
     * @throws \Exception
     * @throws PHPParser_Error
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
