<?php
/**
 * File containing the Config class
 *
 * PHP version 5
 *
 * @category  Php-by-contract
 * @package   TechDivision\PBC
 * @author    Bernhard Wick <b.wick@techdivision.com>
 * @copyright 2014 TechDivision GmbH - <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      http://www.techdivision.com/
 */

namespace TechDivision\PBC;

use TechDivision\PBC\Interfaces\ConfigInterface;
use TechDivision\PBC\Exceptions\ConfigException;
use TechDivision\PBC\Utils\Formatting;
use Psr\Log\LoggerInterface;

/**
 * TechDivision\PBC\Config
 *
 * This class implements the access point for our global (oh no!) configuration
 *
 * @category  Php-by-contract
 * @package   TechDivision\PBC
 * @author    Bernhard Wick <b.wick@techdivision.com>
 * @copyright 2014 TechDivision GmbH - <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      http://www.techdivision.com/
 */
class Config implements ConfigInterface
{

    /**
     * @var array $instances Will contain the instances of this class
     */
    private static $instances = array();

    /**
     * @const string DEFAULT_CONFIG Name of the default configuration file
     */
    const DEFAULT_CONFIG = 'config.default.json';

    /**
     * The delimeter for values names as they are used externally
     *
     * @const string VALUE_NAME_DELIMETER
     */
    const VALUE_NAME_DELIMETER = '/';

    /**
     * @var string $context The context for this instance e.g. app based configurations
     */
    protected $context;

    /**
     * @var array $config Configuration array
     */
    protected $config = array();

    /**
     * Default constructor
     */
    private function __construct()
    {
        $this->load(__DIR__ . DIRECTORY_SEPARATOR . self::DEFAULT_CONFIG);
    }

    /**
     * Will return a singleton instance based on the context we are in
     *
     * @param string $context The context for this instance e.g. app based configurations
     *
     * @return \TechDivision\PBC\Config
     */
    public static function getInstance($context = '')
    {
        if (!isset(self::$instances[$context])) {

            self::$instances[$context] = new self();
        }

        self::$instances[$context]->context = $context;

        return self::$instances[$context];
    }

    /**
     * Will flatten a multi-level associative array into a one-level one
     *
     * @param array  $array       The array to flatten
     * @param string $parentKey   The key of the parent array, used within recursion
     * @param bool   $initialCall Is this the initial call or recursion?
     * @return array
     */
    protected function flattenArray(array $array, $parentKey = '', $initialCall = true)
    {
        $result = array();
        foreach ($array as $key => $value) {

            // If it is an array not containing integer keys (so no nested config element) we have to get recursive
            if (is_array($value) && !is_int(array_keys($value)[0])) {

                $value = $this->flattenArray($value, $key, false);
            }

            // Save the result with a newly combined key
            $result[trim($parentKey . self::VALUE_NAME_DELIMETER . $key, self::VALUE_NAME_DELIMETER)] = $value;
        }

        // If we are within the initial call we would like to do a final flattening and sorting process
        if ($initialCall === true) {

            // No iterate all the entries and array shift them if they are arrays
            foreach ($result as $key => $value) {

                if (is_array($value)) {

                    unset($result[$key]);
                    $result = array_merge($result, $value);
                }
            }

            // Sort the whole thing as we might have mixed it up little
            ksort($result);
        }
        return $result;
    }

    /**
     * Sets a value to specific content
     *
     * @param string $valueName The value to set
     * @param mixed  $value     The actual content for the value
     *
     * @return void
     */
    public function setValue($valueName, $value)
    {
        // Set the value
        $this->config[$valueName] = $value;
    }

    /**
     * Extends a value by specific content. If the value is an array it will be merged, otherwise it will be
     * string-concatinated to the end of the current value
     *
     * @param string $valueName The value to extend
     * @param string $value     The actual content for the value we want to add to the original
     *
     * @return void
     */
    public function extendValue($valueName, $value)
    {
        // Get the original value
        $originalValue = $this->getValue($valueName);

        // If we got an array
        if (is_array($value)) {

            if (is_array($originalValue)) {

                $newValue = array_merge($originalValue, $value);

            } else {

                $newValue = array_merge(array($originalValue), $value);
            }

        } else {

            $newValue = $originalValue . $value;
        }

        // Finally set the new value
        $this->setValue($valueName, $newValue);
    }

    /**
     * Unsets a specific config value
     *
     * @param string $value The value to unset
     *
     * @return void
     */
    public function unsetValue($value)
    {
        if (isset($this->config[$value])) {

            unset($this->config[$value]);
        }
    }

    /**
     * Returns the content of a specific config value
     *
     * @param string $value The value to get the content for
     *
     * @throws \TechDivision\PBC\Exceptions\ConfigException
     *
     * @return mixed
     */
    public function getValue($value)
    {
        // check if server var is set
        if (isset($this->config[$value])) {
            // return server vars value
            return $this->config[$value];
        }
        // throw exception
        throw new ConfigException("Config value '$value' does not exist.");
    }

    /**
     * Checks if value exists for given value
     *
     * @param string $value The value to check
     *
     * @return boolean Weather it has value (true) or not (false)
     */
    public function hasValue($value)
    {
        // check if server var is set
        if (!isset($this->config[$value])) {
            return false;
        }

        return true;
    }

    /**
     * Will load a certain configuration file into this instance. Might throw an exception if the file is not valid
     *
     * @param string $file The path of the configuration file we should load
     *
     * @return null
     *
     * @throws \TechDivision\PBC\Exceptions\ConfigException
     */
    public function load($file)
    {
        // Do we load a valid config?
        $configCandidate = $this->validate($file);
        if ($configCandidate === false) {

            throw new ConfigException('Attempt to load invalid configuration file.');
        }

        $this->config = array_replace_recursive($this->config, $configCandidate);
        self::$instances[$this->context] = $this;

        return self::$instances[$this->context];
    }

    /**
     * Will validate a potential configuration file. Returns false if file is no valid PBC configuration, true otherwise
     *
     * @param string $file Path of the potential configuration file
     *
     * @return array|bool
     * @throws \TechDivision\PBC\Exceptions\ConfigException
     */
    public function isValidConfigFile($file)
    {
        return is_array($this->validate($file));
    }

    /**
     * Will validate a potential configuration file. Returns false if file is no valid PBC configuration.
     * Will return the validated configuration on success
     *
     * @param string $file Path of the potential configuration file
     *
     * @return array|bool
     * @throws \TechDivision\PBC\Exceptions\ConfigException
     */
    protected function validate($file)
    {
        $configCandidate = json_decode(file_get_contents($file), true);

        // Did we even get an array?
        if (!is_array($configCandidate)) {

            throw new ConfigException('Could not parse configuration file ' . $file);

        } else {

            $configCandidate = $this->flattenArray($configCandidate);
        }

        // We need some formatting utilities
        $formattingUtil = new Formatting();

        // We will normalize the paths we got and check if they are valid
        if (isset($configCandidate['cache' . self::VALUE_NAME_DELIMETER . 'dir'])) {
            $tmp = $formattingUtil->normalizePath($configCandidate['cache' . self::VALUE_NAME_DELIMETER . 'dir']);

            if (is_writable($tmp)) {

                $configCandidate['cache' . self::VALUE_NAME_DELIMETER . 'dir'] = $tmp;

            } else {

                return false;
            }
        }

        // Same for enforcement dirs
        $configCandidate = $this->normalizeConfigDirs('enforcement', $configCandidate);

        // Do we still have an array here?
        if (!is_array($configCandidate)) {

            return false;
        }

        // Do the same for the autoloader dirs
        $configCandidate = $this->normalizeConfigDirs('autoloader', $configCandidate);

        // Return what we got
        return $configCandidate;
    }

    /**
     * Will normalize directories mentioned within a configuration aspect.
     * If there is an error false will be returned. If not we will return the given configuration array containing only
     * normalized paths.
     *
     * @param string $configAspect The aspect to check for non-normal dirs
     * @param array  $configArray  The array to check within
     * @return array|bool
     */
    protected function normalizeConfigDirs($configAspect, array $configArray)
    {
        // Are there dirs within this config aspect?
        if (isset($configArray[$configAspect . self::VALUE_NAME_DELIMETER . 'dirs'])) {

            // Get ourselves a format utility
            $formattingUtil = new Formatting();

            // Iterate over all dir entries and normalize the paths
            foreach ($configArray[$configAspect . self::VALUE_NAME_DELIMETER . 'dirs'] as $key => $projectDir) {

                // Do the normalization
                $tmp = $formattingUtil->normalizePath($projectDir);

                if (is_readable($tmp)) {

                    $configArray[$configAspect . self::VALUE_NAME_DELIMETER . 'dirs'][$key] = $tmp;

                } elseif (preg_match('/\[|\]|\*|\+|\.|\(|\)|\?|\^/', $tmp)) {

                    // Kill the original path entry so the iterators wont give us a bad time
                    unset($configArray[$configAspect . self::VALUE_NAME_DELIMETER . 'dirs'][$key]);

                    // We will open up the paths with glob
                    foreach (glob($tmp, GLOB_ERR) as $regexlessPath) {

                        $configArray[$configAspect . self::VALUE_NAME_DELIMETER . 'dirs'][] = $regexlessPath;
                    }

                } else {
                    // Somethings wrong with the path, that should not be

                    return false;
                }
            }

        }

        // Everything seems fine, lets return the changes config array
        return $configArray;
    }

    /**
     * Will return the whole configuration or, if $aspect is given, certain parts of it
     *
     * @param string $aspect The aspect of the configuration we are interested in e.g. 'autoloader'
     *
     * @return array
     */
    public function getConfig($aspect = null)
    {
        if (!is_null($aspect)) {

            // Filter the aspect our of the config
            $tmp = array();
            foreach ($this->config as $key => $value) {

                // Do we have an entry belonging to the certain aspect? If so filter it and cut the aspect key part
                if (strpos($key, $aspect . self::VALUE_NAME_DELIMETER) === 0) {

                    $tmp[str_replace($aspect . self::VALUE_NAME_DELIMETER, '', $key)] = $value;
                }
            }

            return $tmp;

        } else {
            // Just return the whole config

            return $this->config;
        }
    }
}
