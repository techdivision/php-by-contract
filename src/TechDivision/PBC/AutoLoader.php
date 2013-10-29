<?php

namespace TechDivision\PBC;

use TechDivision\PBC\Proxies\ProxyFactory;

/**
 * Class AutoLoader
 * @package TechDivision\PBC
 */
class AutoLoader
{

    /**
     * @var array
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
     * @param $config
     * @param $cache
     */
    public function __construct($config, $cache)
    {
        $this->config = $config;
        $this->cache = $cache;
    }

    /**
     * @param $className
     *
     * @return bool
     */
    public function loadClass($className)
    {
        // Do we have the file in our cache dir?
        $cachePath = __DIR__ . DIRECTORY_SEPARATOR . str_replace('\\', '_', $className) . '.php';
        if ($this->config['Environment'] !== 'development' && is_readable($cachePath)) {

            require $cachePath;
        }

        // Decide if we need to query the proxy factory
        $queryProxy = true;
        if (isset($this->config['AutoLoader']['omit'])) {

            foreach ($this->config['AutoLoader']['omit'] as $omitted) {

                // If our class name begins with the omitted part e.g. it's namespace
                if (strpos($className, $omitted) === 0) {

                    $queryProxy = false;
                    break;
                }
            }
        }

        // Do need to query our ProxyFactory?
        if ($queryProxy == true) {

            // Still here? Then we have to check the cache.
            if ($this->cache === null) {

                $this->cache = new CacheMap($this->config['AutoLoader']['projectRoot']);
            }
            $fileMap = new StructureMap($this->config['AutoLoader']['projectRoot']);
            $this->proxyFactory = new ProxyFactory($fileMap, $this->cache);

            // If we do not have the class in our proxy cache
            if ($this->config['Environment'] === 'development' ||
                $this->cache->entryExists($className) === false ||
                $this->cache->isCurrent($className) === false) {

                // Create our proxy class
                $this->proxyFactory->createProxy($className);
            }

            // Require the proxy class, it should have been created now
            $proxyFile = $this->proxyFactory->getProxyFileName($className);

            if (is_readable($proxyFile) === true) {

                require $proxyFile;
                return true;
            }
        }

        return false;
    }

    /**
     *
     */
    public function register()
    {
        // We want to let our autoloader be the first in line so we can react on loads and create/return our proxies.
        // So lets use the prepend parameter here.
        spl_autoload_register(array($this, self::OUR_LOADER), true, true);
    }
}