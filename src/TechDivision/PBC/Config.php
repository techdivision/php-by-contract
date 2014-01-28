<?php
/**
 * TechDivision\PBC\Config
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 */
namespace TechDivision\PBC;

use TechDivision\PBC\Interfaces\ConfigInterface;
use Psr\Log\LoggerInterface;

/**
 * @package     TechDivision\PBC
 * @copyright   Copyright (c) 2013 <info@techdivision.com> - TechDivision GmbH
 * @license     http://opensource.org/licenses/osl-3.0.php
 *              Open Software License (OSL 3.0)
 * @author      Bernhard Wick <b.wick@techdivision.com>
 */
class Config implements ConfigInterface
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
        $this->load(__DIR__ . DIRECTORY_SEPARATOR . self::DEFAULT_CONFIG);
    }

    /**
     * @param null   $configFile
     * @param string $context
     *
     * @return mixed
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

    protected function checkRecency()
    {

    }

    /**
     * @param $file
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

        // We will normalize the pathes we got and check if they are valid
        if (isset($configCandidate['cache']['dir'])) {
            $tmp = $this->normalizePath($configCandidate['cache']['dir']);

            if (is_writable($tmp)) {

                $configCandidate['cache']['dir'] = $tmp;

            } else {

                return false;
            }
        }

        // Same for project-dirs
        if (isset($configCandidate['project-dirs'])) {
            foreach ($configCandidate['project-dirs'] as $key => $projectDir) {

                $tmp = $this->normalizePath($projectDir);

                if (is_readable($tmp)) {

                    $configCandidate['project-dirs'][$key] = $tmp;

                } elseif (preg_match('/\[|\]|\*|\+|\.|\(|\)|\?|\^/', $tmp)) {

                    // Kill the original path entry so the iterators wont give us a bad time
                    unset($configCandidate['project-dirs'][$key]);

                    // We will open up the paths with glob
                    foreach (glob($tmp, GLOB_ERR) as $regexlessPath) {

                        $configCandidate['project-dirs'][] = $regexlessPath;
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

    /**
     * Will break up any path into a canonical form like realpath(), but does not require the file to exist.
     *
     * @param $path
     *
     * @return mixed
     */
    private function normalizePath($path)
    {
        return array_reduce(
            explode('/', $path),
            create_function(
                '$a, $b',
                '
                           if($a === 0)
                               $a = "/";

                           if($b === "")
                               return $a;

                           if($b === ".")
                               return __DIR__;

                           if($b === "..")
                               return dirname($a);

                           return preg_replace("/\/+/", "/", "$a/$b");
                       '
            ),
            0
        );
    }
}
