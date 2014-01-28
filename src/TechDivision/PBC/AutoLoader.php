<?php
/**
 * TechDivision\PBC\AutoLoader
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 */
namespace TechDivision\PBC;

// Load the constants if not already done
require_once __DIR__ . DIRECTORY_SEPARATOR . 'Constants.php';

/**
 * @package     TechDivision\PBC
 * @copyright   Copyright (c) 2013 <info@techdivision.com> - TechDivision GmbH
 * @license     http://opensource.org/licenses/osl-3.0.php
 *              Open Software License (OSL 3.0)
 * @author      Bernhard Wick <b.wick@techdivision.com>
 */
class AutoLoader
{

    /**
     * @var Config
     */
    private $config;

    /**
     * @var CacheMap
     */
    private $cache;

    /**
     * @var Generator
     */
    private $generator;

    /**
     * @const
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
     * @param $className
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
     *
     */
    public function register($throws = true)
    {
        // We want to let our autoloader be the first in line so we can react on loads
        // and create/return our contracted definitions.
        // So lets use the prepend parameter here.
        spl_autoload_register(array($this, self::OUR_LOADER), $throws, true);
    }
}
