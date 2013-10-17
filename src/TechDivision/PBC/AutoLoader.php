<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 25.06.13
 * Time: 17:13
 * To change this template use File | Settings | File Templates.
 */

namespace TechDivision\PBC;

use TechDivision\PBC\Proxies\ProxyFactory;
use TechDivision\PBC\Interfaces\PBCCache;

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
     * @var Interfaces\PBCCache
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
     * @param PBCCache $cache
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
        // Decide if we need to query the proxy factory
        $queryProxy = true;
        if (isset($this->config['omit'])) {

            foreach ($this->config['omit'] as $omitted) {

                // If our class name begins with the omitted part e.g. it's namespace
                if (strpos($className, $omitted) === 0) {

                    $queryProxy = false;
                    break;
                }
            }
        }

        // Do need to query our ProxyFactory?
        if ($queryProxy == true) {

            // If we do not have the class in our proxy cache
            if ($this->cache->entryExists($className) === false || $this->cache->isCurrent($className) === false) {

                // Create our proxy class
                $fileMap = new StructureMap($this->config['projectRoot']);
                $this->proxyFactory = new ProxyFactory($fileMap, $this->cache);
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