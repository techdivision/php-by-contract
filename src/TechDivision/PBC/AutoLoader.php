<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 25.06.13
 * Time: 17:13
 * To change this template use File | Settings | File Templates.
 */

namespace TechDivision\PBC;

require_once __DIR__ . "/../../vendor/symfony/class-loader/Symfony/Component/ClassLoader/UniversalClassLoader.php";
require_once __DIR__ . "/Proxies/ProxyFactory.php";

use Symfony\Component\ClassLoader\UniversalClassLoader;
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
     * @const
     */
    const OUR_LOADER = 'loadClass';

    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * @param $className
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

            // Get a proxyFactory
            $proxyFactory = new ProxyFactory($this->config['projectRoot']);

            // If we do not have the class in our proxy cache
            if ($proxyFactory->isCached($className) === false) {

                // Create our proxy class
                $proxyFactory->createProxy($className);
            }

            // Require the proxy class, it should have been created now
            $proxyFile = $proxyFactory->getProxyFileName($className);
            if (is_readable($proxyFile) === true) {

                require $proxyFile;
            }
        }
    }

    /**
     *
     */
    public function register()
    {
        // We want to let our autoloader be the first in line so we can react on loads and create/return our proxies.
        // So lets use the prepend parameter here.
        spl_autoload_register(array($this, self::OUR_LOADER), true, true);

        // Also register "our" Symfony ClassLoader so we have a PSR-0 compatible loader at hand.
        // Register this one to append to the autoloader stack.
        $loader = new UniversalClassLoader();
        $loader->registerNamespace("Symfony 2.2.3 2.1.11.3.1\\Component", "../vendor/symfony/class-loader/Symfony 2.2.3 2.1.11.3.1/Component/");
        $loader->registerNamespace("TechDivision\\PBC", realpath('../../'));
        $loader->register(false);
    }
}