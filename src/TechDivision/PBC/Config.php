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
     * Will load a certain configuration file into this instance. Might throw an exception if the file is not valid
     *
     * @param string $file The path of the configuration file we should load
     *
     * @return null
     *
     * @throws \Exception
     */
    public function load($file)
    {
        // Do we load a valid config?
        $configCandidate = $this->validate($file);
        if ($configCandidate === false) {

            throw new \Exception('Attempt to load invalid configuration file.');
        }

        $this->config = array_replace_recursive($this->config, $configCandidate);
        self::$instances[$this->context] = $this;

        return self::$instances[$this->context];
    }

    /**
     * Will validate a potential configuration file. Returns false if file is no valid PBC configuration
     *
     * @param string $file Path of the potential configuration file
     *
     * @return bool
     * @throws \Exception
     */
    public function validate($file)
    {
        $configCandidate = json_decode(file_get_contents($file), true);

        // Did we even get an array?
        if (!is_array($configCandidate)) {

            throw new \Exception('Could not parse configuration file ' . $file);
        }

        // We need some formatting utilities
        $formattingUtil = new Formatting();

        // We will normalize the pathes we got and check if they are valid
        if (isset($configCandidate['cache']['dir'])) {
            $tmp = $formattingUtil->normalizePath($configCandidate['cache']['dir']);

            if (is_writable($tmp)) {

                $configCandidate['cache']['dir'] = $tmp;

            } else {

                return false;
            }
        }

        // Same for enforcement dirs
        if (isset($configCandidate['enforcement']['dirs'])) {
            foreach ($configCandidate['enforcement']['dirs'] as $key => $projectDir) {

                $tmp = $formattingUtil->normalizePath($projectDir);

                if (is_readable($tmp)) {

                    $configCandidate['enforcement']['dirs'][$key] = $tmp;

                } elseif (preg_match('/\[|\]|\*|\+|\.|\(|\)|\?|\^/', $tmp)) {

                    // Kill the original path entry so the iterators wont give us a bad time
                    unset($configCandidate['enforcement']['dirs'][$key]);

                    // We will open up the paths with glob
                    foreach (glob($tmp, GLOB_ERR) as $regexlessPath) {

                        $configCandidate['enforcement']['dirs'][] = $regexlessPath;
                    }

                } else {

                    return false;
                }
            }
        }

        // Same for autoloader dirs
        if (isset($configCandidate['autoloader']['dirs'])) {
            foreach ($configCandidate['autoloader']['dirs'] as $key => $projectDir) {

                $tmp = $formattingUtil->normalizePath($projectDir);

                if (is_readable($tmp)) {

                    $configCandidate['autoloader']['dirs'][$key] = $tmp;

                } elseif (preg_match('/\[|\]|\*|\+|\.|\(|\)|\?|\^/', $tmp)) {

                    // Kill the original path entry so the iterators wont give us a bad time
                    unset($configCandidate['autoloader']['dirs'][$key]);

                    // We will open up the paths with glob
                    foreach (glob($tmp, GLOB_ERR) as $regexlessPath) {

                        $configCandidate['autoloader']['dirs'][] = $regexlessPath;
                    }

                } else {

                    return false;
                }
            }
        }

        // There was no error till now, so return true.
        return $configCandidate;
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
        if (!is_null($aspect) && isset($this->config[$aspect])) {

            return $this->config[$aspect];

        } else {

            return $this->config;
        }
    }
}
