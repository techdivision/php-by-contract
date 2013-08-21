<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 20.06.13
 * Time: 17:25
 * To change this template use File | Settings | File Templates.
 */

// I don't know how to handle that better, forgive me (or better: explain me how!) TODO
if (is_dir(__DIR__ . "/../../../vendor")) {

    require_once __DIR__ . "/../../../vendor/symfony/class-loader/Symfony/Component/ClassLoader/UniversalClassLoader.php";

} elseif (is_dir(__DIR__ . "/../../../../../symfony")) {

    require_once __DIR__ . "/../../../../../symfony/class-loader/Symfony/Component/ClassLoader/UniversalClassLoader.php";

}

// Load the constants
require_once 'Constants.php';

use TechDivision\PBC\AutoLoader;
use TechDivision\PBC\Proxies\Cache;
use TechDivision\PBC\Config;
use Symfony\Component\ClassLoader\UniversalClassLoader;


// Register "our" Symfony ClassLoader so we have a PSR-0 compatible loader at hand.
// Register this one to append to the autoloader stack.
$loader = new UniversalClassLoader();
$loader->registerNamespace("Symfony\\Component", realpath(__DIR__ . "/../../../vendor/symfony/class-loader/Symfony/Component/"));
$loader->registerNamespace("TechDivision\\PBC", realpath(__DIR__ . '/../../'));
$loader->register(false);

// We have to register our autoLoader to put our proxies in place
$config = new Config();
$config = $config->getConfig('AutoLoader');

$cache = new Cache($config['projectRoot']);
$autoLoader = new AutoLoader($config, $cache);
$autoLoader->register();

