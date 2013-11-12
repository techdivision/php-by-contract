<?php
/**
 * TechDivision\PBC\StreamFilters\ProcessingFilter
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 */

namespace TechDivision\PBC\StreamFilters;

use TechDivision\PBC\Exceptions\GeneratorException;

/**
 * @package     TechDivision\PBC
 * @subpackage  StreamFilters
 * @copyright   Copyright (c) 2013 <info@techdivision.com> - TechDivision GmbH
 * @license     http://opensource.org/licenses/osl-3.0.php
 *              Open Software License (OSL 3.0)
 * @author      Bernhard Wick <b.wick@techdivision.com>
 */
class ProcessingFilter
{

    /**
     * @const   int
     */
    const FILTER_ORDER = 4;

    /**
     * @var array
     */
    private $dependencies = array(array('PreconditionFilter', 'PostconditionFilter', 'InvariantFilter'));

    /**
     * @var array
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
     * @return int
     * @throws \TechDivision\PBC\Exceptions\GeneratorException
     */
    public function filter($in, $out, &$consumed, $closing)
    {
        // Get our buckets from the stream
        while ($bucket = stream_bucket_make_writeable($in)) {

            // Lets check if we got the config we wanted
            $config = $this->params;
            if (!is_array($config) || !isset($config['processing'])) {

                throw new GeneratorException();
            }

            // Get the code for the assertions
            $code = $this->generateCode($config);

            // Insert the code
            $bucket->data = str_replace(PBC_PROCESSING_PLACEHOLDER, $code, $bucket->data);

            // "Destroy" the code
            $code = null;

            // Tell them how much we already processed, and stuff it back into the output
            $consumed += $bucket->datalen;
            stream_bucket_append($out, $bucket);
        }

        return PSFS_PASS_ON;
    }

    /**
     * @param $config
     * @return string
     */
    private function generateCode($config)
    {
        $code = '';

        // What kind of reaction should we create?
        switch ($config['processing']) {

            case 'exception':

                // What kind of exception do we need?
                switch ($for) {

                    case 'precondition':

                        $exception = 'BrokenPreconditionException';
                        break;

                    case 'postcondition':

                        $exception = 'BrokenPostconditionException';
                        break;

                    case 'invariant':

                        $exception = 'BrokenInvariantException';
                        break;

                    default:

                        $exception = '\Exception';
                        break;
                }
                // Create the code
                $code .= '$this->' . PBC_CONTRACT_DEPTH . '--;
                throw new ' . $exception . '(\'' . $message . '\');';

                break;

            case 'logging':

                // Create the code
                $code .= '$logger = new \\' . $config['logger'] . '();
                $logger->error(\'Broken ' . $for . ' with message: ' . $message . ' in \' . __METHOD__);';
                break;

            default:

                break;
        }

        return $code;
    }
} 