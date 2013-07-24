<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 20.06.13
 * Time: 17:25
 * To change this template use File | Settings | File Templates.
 */

require_once __DIR__ . "/AutoLoader.php";
require_once __DIR__ . "/Config.php";

use TechDivision\PBC\AutoLoader;
use TechDivision\PBC\Config;

// We have to register our autoLoader to put our proxies in place
$config = new Config();
$autoLoader = new AutoLoader($config->getConfig('AutoLoader'));
$autoLoader->register();

