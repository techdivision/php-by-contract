<?php

namespace TechDivision\PBC;

use TechDivision\PBC\Proxies\ProxyFactory;

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
     * @var Proxies\ProxyFactory
     */
    private $proxyFactory;

    /**
     * @const
     */
    const OUR_LOADER = 'loadClass';


    /**
     * Default constructor
     */
    public function __construct()
    {
        $this->config = new Config();
        $this->cache = null;
    }

    /**
     * @param $className
     *
     * @return bool
     */
    public function loadClass($className)
    {
        // Do we have the file in our cache dir? If we are in development mode we have to ignore this.
        $cachePath = __DIR__ . DIRECTORY_SEPARATOR . str_replace('\\', '_', $className) . '.php';
        if ($this->config->getConfig('Environment') !== 'development' && is_readable($cachePath)) {

            require $cachePath;
            return true;
        }

        // There was no file in our cache dir, so lets hope we know the original path of the file.
        $autoLoaderConfig = $this->config->getConfig('AutoLoader');

        // We also require the classes of our maps as we do not have proper autoloading in place
        require_once __DIR__ . DIRECTORY_SEPARATOR . 'StructureMap.php';
        $structureMap = new StructureMap($autoLoaderConfig['projectRoot'], $this->config);
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
            $this->cache = new CacheMap(PBC_CACHE_DIR, $this->config);
        }
        $this->proxyFactory = new ProxyFactory($structureMap, $this->cache);

        // Create the new class definition
        if ($this->proxyFactory->createProxy($className) === true) {

            // Require the proxy class, it should have been created now
            $proxyFile = $this->proxyFactory->getProxyFileName($className);

            if (is_readable($proxyFile) === true) {

                require $proxyFile;
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
        // We want to let our autoloader be the first in line so we can react on loads and create/return our proxies.
        // So lets use the prepend parameter here.
        spl_autoload_register(array($this, self::OUR_LOADER), $throws, true);
    }
}