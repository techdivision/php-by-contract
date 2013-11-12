<?php
/**
 * TechDivision\PBC\StreamFilters\PreconditionFilter
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 */

namespace TechDivision\PBC\StreamFilters;

use TechDivision\PBC\Entities\Lists\FunctionDefinitionList;

/**
 * @package     TechDivision\PBC
 * @subpackage  StreamFilters
 * @copyright   Copyright (c) 2013 <info@techdivision.com> - TechDivision GmbH
 * @license     http://opensource.org/licenses/osl-3.0.php
 *              Open Software License (OSL 3.0)
 * @author      Bernhard Wick <b.wick@techdivision.com>
 */
class PreconditionFilter extends AbstractFilter
{
    /**
     * @var string
     */
    private $filterName;

    /**
     * @const   int
     */
    const FILTER_ORDER = 1;

    /**
     * @var FunctionDefinitionList
     */
    private $functionDefinitionList;

    /**
     *
     */
    public function __construct(FunctionDefinitionList $functionDefinitionList)
    {
        // "Calculate" the filter name
        $this->filterName = lcfirst(__CLASS__);

        // Get the list of functions we have to work on
        $this->functionDefinitionList = $functionDefinitionList;
    }

    /**
     * @param $in
     * @param $out
     * @param $consumed
     * @param $closing
     * @return int
     */
    function filter($in, $out, &$consumed, $closing)
    {
        // Get our buckets from the stream
        while ($bucket = stream_bucket_make_writeable($in)) {

            // Get the tokens
            $tokens = token_get_all($bucket->data);

            $tokensCount = count($tokens);
            for ($i = 0; $i < $tokensCount; $i++) {

                if (is_array($tokens[$i]) && $tokens[$i][0] === T_FUNCTION) {

                    $replacementString = '';
                    for ($j = $i; $j < $tokensCount; $j++) {

                        if (is_array($tokens[$j])) {

                            $replacementString .= $tokens[$j][1];

                        } else {

                            $replacementString .= $tokens[$j];

                            if ($tokens[$j] === '{') {

                                break;
                            }
                        }

                    }

                    $bucket->data = str_replace($replacementString, $replacementString . '//TEST', $bucket->data);
                }

            }

            $consumed += $bucket->datalen;
            stream_bucket_append($out, $bucket);
        }

        return PSFS_PASS_ON;
    }


    function onCreate()
    {
    }


    public function onClose()
    {
    }

    /**
     * @return string
     */
    public function getFilterName()
    {
        return $this->filterName;
    }

    /**
     * @return int
     */
    public function getFilterOrder()
    {
        return self::FILTER_ORDER;
    }
}