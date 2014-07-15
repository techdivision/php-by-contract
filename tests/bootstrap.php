<?php
/**
 * File bootstrapping the PHPUnit test environment
 *
 * PHP version 5
 *
 * @category   Php-by-contract
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 *             Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */

$vendorDir = '';
if (realpath(__DIR__ . "/../../../../vendor")) {

    $vendorDir = realpath(__DIR__ . "/../../../../vendor");

} else {

    throw new Exception('Could not locate vendor dir');
}

// Include the composer autoloader as a fallback
$loader = require $vendorDir . DIRECTORY_SEPARATOR . 'autoload.php';
$loader->add('TechDivision\\PBC\\', $vendorDir . DIRECTORY_SEPARATOR . 'techdivision/php-by-contract/src');

// Load the test config file
$config = TechDivision\PBC\Config::getInstance();
$config->load(
    __DIR__ . DIRECTORY_SEPARATOR . 'TechDivision' . DIRECTORY_SEPARATOR . 'PBC' .
    DIRECTORY_SEPARATOR . 'Tests' . DIRECTORY_SEPARATOR . 'Data' . DIRECTORY_SEPARATOR . 'tests.conf.json'
);

// We have to register our autoLoader to put our proxies in place
$autoLoader = new TechDivision\PBC\AutoLoader();
$autoLoader->register();