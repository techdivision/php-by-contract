<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 20.06.13
 * Time: 17:25
 * To change this template use File | Settings | File Templates.
 */

// I don't know how to handle that better, forgive me (or better: explain me how!) TODO
$vendorDir = '';
if (is_dir(__DIR__ . "/../../../vendor")) {

    $vendorDir = __DIR__ . "/../../../vendor/";

} elseif (is_dir(__DIR__ . "/../../../../../../vendor")) {

    $vendorDir = __DIR__ . "/../../../../../../vendor/";
}

// Include the composer autoloader as a fallback
require_once $vendorDir . 'autoload.php';

use TechDivision\PBC\AutoLoader;

// We have to register our autoLoader to put our proxies in place
$autoLoader = new AutoLoader();
$autoLoader->register();
