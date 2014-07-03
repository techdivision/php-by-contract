<?php
/**
 * File containing the ConfigInterface interface
 *
 * PHP version 5
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Interfaces
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */

namespace TechDivision\PBC\Interfaces;

/**
 * TechDivision\PBC\Interfaces\ConfigInterface
 *
 * An interface defining the functionality of any possible configuration class
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Interfaces
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */
interface ConfigInterface
{
    /**
     * Will return a singleton instance based on the context we are in
     *
     * @param string $context The context for this instance e.g. app based configurations
     *
     * @return \TechDivision\PBC\Config
     */
    public static function getInstance($context = '');

    /**
     * Will load a certain configuration file into this instance. Might throw an exception if the file is not valid
     *
     * @param string $file The path of the configuration file we should load
     *
     * @return null
     *
     * @throws \Exception
     */
    public function load($file);

    /**
     * Will validate a potential configuration file. Returns false if file is no valid PBC configuration
     *
     * @param string $file Path of the potential configuration file
     *
     * @return bool
     * @throws \Exception
     */
    public function validate($file);
}
