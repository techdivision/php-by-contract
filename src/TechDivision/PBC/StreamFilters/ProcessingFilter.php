<?php
/**
 * File containing the ProcessingFilter class
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

use TechDivision\PBC\Exceptions\ExceptionFactory;
use TechDivision\PBC\Exceptions\GeneratorException;

/**
 * TechDivision\PBC\StreamFilters\ProcessingFilter
 *
 * This filter will buffer the input stream and add the processing information into the prepared assertion checks
 * (see $dependencies)
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage StreamFilters
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */
class ProcessingFilter extends AbstractFilter
{

    /**
     * @const integer FILTER_ORDER Order number if filters are used as a stack, higher means below others
     */
    const FILTER_ORDER = 4;

    /**
     * @var array $dependencies Other filters on which we depend
     */
    private $dependencies = array(array('PreconditionFilter', 'PostconditionFilter', 'InvariantFilter'));

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
     * @return boolean
     */
    public function dependenciesMet()
    {
        return true;
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
        // Lets check if we got the config we wanted
        $config = $this->params->getConfig('enforcement');

        if (!is_array($config) || !isset($config['processing'])) {

            throw new GeneratorException();
        }

        // Get the code for the processing
        $preconditionCode = $this->generateCode($config, 'precondition');
        $postconditionCode = $this->generateCode($config, 'postcondition');
        $invariantCode = $this->generateCode($config, 'invariant');
        $invalidCode = $this->generateCode($config, 'InvalidArgumentException');
        $missingCode = $this->generateCode($config, 'MissingPropertyException');

        // Get our buckets from the stream
        while ($bucket = stream_bucket_make_writeable($in)) {

            // Insert the code for the static processing placeholders
            $bucket->data = str_replace(
                array(
                    PBC_PROCESSING_PLACEHOLDER . 'precondition' . PBC_PLACEHOLDER_CLOSE,
                    PBC_PROCESSING_PLACEHOLDER . 'postcondition' . PBC_PLACEHOLDER_CLOSE,
                    PBC_PROCESSING_PLACEHOLDER . 'invariant' . PBC_PLACEHOLDER_CLOSE,
                    PBC_PROCESSING_PLACEHOLDER . 'InvalidArgumentException' . PBC_PLACEHOLDER_CLOSE,
                    PBC_PROCESSING_PLACEHOLDER . 'MissingPropertyException' . PBC_PLACEHOLDER_CLOSE
                ),
                array($preconditionCode, $postconditionCode, $invariantCode, $invalidCode, $missingCode),
                $bucket->data
            );

            // Tell them how much we already processed, and stuff it back into the output
            $consumed += $bucket->datalen;
            stream_bucket_append($out, $bucket);
        }

        return PSFS_PASS_ON;
    }

    /**
     * /**
     * Will generate the code needed to enforce any broken assertion checks
     *
     * @param array  $config The configuration aspect which holds needed information for us
     * @param string $for    For which kind of assertion do wee need the processing
     *
     * @return string
     */
    private function generateCode($config, $for)
    {
        $code = '';

        // Code defining the place the error happened
        $place = '__METHOD__';

        // If we are in an invariant we should tell them about the method we got called from
        if ($for === 'invariant') {

            $place = '$callingMethod';
        }

        // What kind of reaction should we create?
        switch ($config['processing']) {

            case 'exception':

                $exceptionFactory = new ExceptionFactory();
                $exception = $exceptionFactory->getClassName($for);

                // Create the code
                $code .= '\TechDivision\PBC\ContractContext::close();
                throw new \\' . $exception . '("Failed ' . PBC_FAILURE_VARIABLE . ' in " . ' . $place . ');';

                break;

            case 'logging':

                // Create the code
                $code .= '$container = new \TechDivision\PBC\Utils\InstanceContainer();
                $logger = $container[PBC_LOGGER_CONTAINER_ENTRY];
                $logger->error("Failed ' . $for .
                    PBC_FAILURE_VARIABLE . ' in " . ' . $place . ');';
                break;

            default:

                break;
        }

        return $code;
    }
}
