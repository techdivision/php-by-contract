<?php
/**
 * File containing the AutoLoader class
 *
 * PHP version 5
 *
 * @category  Php-by-contract
 * @package   TechDivision\PBC
 * @author    Bernhard Wick <b.wick@techdivision.com>
 * @copyright 2014 TechDivision GmbH - <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php
 *            Open Software License (OSL 3.0)
 * @link      http://www.techdivision.com/
 */

namespace TechDivision\PBC;

// Load the constants if not already done
require_once __DIR__ . DIRECTORY_SEPARATOR . 'Constants.php';

/**
 * TechDivision\PBC\AutoLoader
 *
 * Will provide autoloader functionality as an entry point for parsing and code generation
 *
 * @category  Php-by-contract
 * @package   TechDivision\PBC
 * @author    Bernhard Wick <b.wick@techdivision.com>
 * @copyright 2014 TechDivision GmbH - <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php
 *            Open Software License (OSL 3.0)
 * @link      http://www.techdivision.com/
 */
class AutoLoader
{

    /**
     * @var \TechDivision\PBC\Config $config The configuration we base our actions on
     */
    private $config;

    /**
     * @var \TechDivision\PBC\CacheMap $cache Cache map to keep track of already processed files
     */
    private $cache;

    /**
     * @var \TechDivision\PBC\Generator $generator Generator instance if we need to create a new definition
     */
    private $generator;

    /**
     * @const string OUR_LOADER Name of our class loading method as we will register it
     */
    const OUR_LOADER = 'loadClass';


    /**
     * Default constructor
     */
    public function __construct()
    {
        $this->config = Config::getInstance();
        $this->cache = null;
    }

    /**
     * Will load any given structure based on it's availability in our structure map which depends on the configured
     * project directories.
     * If the structure cannot be found we will redirect to the composer autoloader which we registered as a fallback
     *
     * @param string $className The name of the structure we will try to load
     *
     * @return bool
     */
    public function loadClass($className)
    {
        // There was no file in our cache dir, so lets hope we know the original path of the file.
        $autoLoaderConfig = $this->config->getConfig('autoloader');

        // Might the class be a omitted one? If so we can require the original.
        if (isset($autoLoaderConfig['omit'])) {

            foreach ($autoLoaderConfig['omit'] as $omitted) {

                // If our class name begins with the omitted part e.g. it's namespace
                if (strpos($className, $omitted) === 0) {

                    return false;
                }
            }
        }

        // Do we have the file in our cache dir? If we are in development mode we have to ignore this.
        $cacheConfig = $this->config->getConfig('cache');
        if ($this->config->getConfig('environment') !== 'development') {

            $cachePath = $cacheConfig['dir'] . DIRECTORY_SEPARATOR . str_replace('\\', '_', $className) . '.php';

            if (is_readable($cachePath)) {

                $res = fopen($cachePath, 'r');
                $str = fread($res, 384);

                $success = preg_match(
                    '/' . PBC_ORIGINAL_PATH_HINT . '(.+)' .
                    PBC_ORIGINAL_PATH_HINT . '/',
                    $str,
                    $tmp
                );

                if ($success > 0) {

                    $tmp = explode('#', $tmp[1]);

                    $path = $tmp[0];
                    $mTime = $tmp[1];

                    if (filemtime($path) == $mTime) {

                        require $cachePath;


                        return true;
                    }
                }
            }
        }

        // If we are loading something of our own library we can skip to composer
        if ((strpos($className, 'TechDivision\PBC') === 0 && strpos($className, 'TechDivision\PBC\Tests') === false) ||
            strpos($className, 'PHP') === 0
        ) {

            return false;
        }

        // We also require the classes of our maps as we do not have proper autoloading in place
        $structureMap = new StructureMap($this->config->getConfig('project-dirs'), $this->config);
        $file = $structureMap->getEntry($className);

        // Did we get something? If not return false.
        if ($file === false) {

            return false;
        }

        // We are still here, so we know the class and it is not omitted. Does it contain contracts then?
        if ($file->hasContracts() === false) {

            require $file->getPath();

            return true;
        }

        // So we have to create a new class definition for this original class.
        // Get a current cache instance if we do not have one already.
        if ($this->cache === null) {

            // We also require the classes of our maps as we do not have proper autoloading in place
            $this->cache = new CacheMap($cacheConfig['dir'], $this->config);
        }
        $this->generator = new Generator($structureMap, $this->cache);

        // Create the new class definition
        if ($this->generator->create($file) === true) {

            // Require the new class, it should have been created now
            $file = $this->generator->getFileName($className);
            if (is_readable($file) === true) {

                require $file;

                return true;
            }

        } else {

            return false;
        }

        // Still here? That sounds like bad news!
        return false;
    }

    /**
     * Will register our autoloading method at the beginning of the spl autoloader stack
     *
     * @param bool $throw Should we throw an exception on error?
     *
     * @return null
     */
    public function register($throw = true)
    {
        // We want to let our autoloader be the first in line so we can react on loads
        // and create/return our contracted definitions.
        // So lets use the prepend parameter here.
        spl_autoload_register(array($this, self::OUR_LOADER), $throw, true);
    }
}
