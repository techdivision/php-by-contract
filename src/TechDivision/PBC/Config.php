<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 26.06.13
 * Time: 09:44
 * To change this template use File | Settings | File Templates.
 */

namespace TechDivision\PBC;

use TechDivision\PBC\Interfaces\PBCConfig;
use Psr\Log\LoggerInterface;

class Config implements PBCConfig
{

    /**
     * @var null|Config
     */
    private static $instances = array();

    /**
     * @const   string
     */
    const DEFAULT_CONFIG = 'config.default.json';

    /**
     * @var array
     */
    protected $context;

    /**
     * @var array
     */
    protected $config = array();

    /**
     *
     */
    private function __construct()
    {
        if ($this->validate(__DIR__ . DIRECTORY_SEPARATOR . self::DEFAULT_CONFIG)) {

            $this->load(__DIR__ . DIRECTORY_SEPARATOR . self::DEFAULT_CONFIG);

        } else {

            throw new \Exception('Invalid default configuration.');
        }
    }

    /**
     * @param string $context
     * @return Config
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
     * @param   string $file
     * @throws \Exception
     */
    public function load($file)
    {
        // Do we load a valid config?
        if (!$this->validate($file)) {

            throw new \Exception('Attempt to load invalid configuration file.');

        }

        $configCandidate = json_decode(file_get_contents($file), true);

        // Did we even get an array?
        if (!is_array($configCandidate)) {

            throw new \Exception('Could not parse configuration file ' . $file);
        }

        $this->config = array_replace_recursive($this->config, $configCandidate);
        self::$instances[$this->context] = $this;
        return self::$instances[$this->context];
    }

    /**
     *
     */
    public function validate($file)
    { /*
        // Check if we have to use a logger, and if so check if it complies with PSR-3.
        if ($this->config['Enforcement']['processing'] === 'logging') {

            // Instantiate our logger candidate
            $loggerCandidate = $this->config['Enforcement']['logger'];
            $loggerInterfaces = class_implements($loggerCandidate);

            // Does it implement the PSR-3 interface?
            if (!isset($loggerInterfaces['Psr\Log\LoggerInterface'])) {

                // Logger does not satisfy PSR-3, lets set processing to exception
                $this->config['Enforcement']['processing'] = 'exception';
            }
        }
        */
        // There was no error till now, so return true.
        return true;
    }

    /**
     * @param string $aspect
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

