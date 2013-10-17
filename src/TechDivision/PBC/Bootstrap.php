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

    $vendorDir = __DIR__ . "/../../../vendor/";

} elseif (is_dir(__DIR__ . "/../../vendor")) {

    $vendorDir = __DIR__ . "/../../vendor/";
}

// Load the constants
require_once 'Constants.php';
require_once $vendorDir . 'autoload.php';

use TechDivision\PBC\AutoLoader;
use TechDivision\PBC\Proxies\Cache;
use TechDivision\PBC\Config;

// We have to register our autoLoader to put our proxies in place
$config = new Config();
$config = $config->getConfig('AutoLoader');

$autoLoader = new AutoLoader($config);
$autoLoader->register();

