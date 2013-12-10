<?php

namespace TechDivision\PBC;

// Load the constants if not already done
require_once __DIR__ . DIRECTORY_SEPARATOR . 'Constants.php';

/**
 * Class AutoLoader
 *
 * @package TechDivision\PBC
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
     * Will break up any path into a canonical form like realpath(), but does not require the file to exist.
     *
     * @param $path
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

    /**
     * @param $className
     *
     * @return bool
     */
    public function loadClass($className)
    {

        // Do we have the file in our cache dir? If we are in development mode we have to ignore this.
        $cacheConfig = $this->config->getConfig('cache');
        if ($this->config->getConfig('environment') !== 'development') {

            $cachePath = $this->normalizePath(
                $cacheConfig['dir'] . DIRECTORY_SEPARATOR . str_replace('\\', '_', $className) . '.php'
            );

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

        // There was no file in our cache dir, so lets hope we know the original path of the file.
        $autoLoaderConfig = $this->config->getConfig('autoloader');

        // We also require the classes of our maps as we do not have proper autoloading in place
        require_once __DIR__ . DIRECTORY_SEPARATOR . 'StructureMap.php';
        $structureMap = new StructureMap($this->config->getConfig('project-dirs'), $this->config);
        $file = $structureMap->getEntry($className);

        // Did we get something? If not return false.
        if ($file === false) {

            return false;
        }

        // Might the class be a omitted one? If so we can require the original.
        if (isset($autoLoaderConfig['omit'])) {

            foreach ($autoLoaderConfig['omit'] as $omitted) {

                // If our class name begins with the omitted part e.g. it's namespace
                if (strpos($className, $omitted) === 0) {

                    require $file->getPath();
                    return true;
                }
            }
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
            require_once __DIR__ . DIRECTORY_SEPARATOR . 'CacheMap.php';
            $this->cache = new CacheMap($cacheConfig['dir'], $this->config);
        }
        $this->generator = new Generator($structureMap, $this->cache);

        // Create the new class definition
        if ($this->generator->createProxy($className) === true) {

            // Require the new class, it should have been created now
            $file = $this->generator->getProxyFileName($className);
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