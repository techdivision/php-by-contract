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

// Load PBCs bootstrapping
require_once '${php-target.dir}/${codepool}/${namespace}/${module}/src/TechDivision/PBC/Bootstrap.php';

// Load the test config file
$config = TechDivision\PBC\Config::getInstance();
$config->load(
    __DIR__ . DIRECTORY_SEPARATOR . 'TechDivision' . DIRECTORY_SEPARATOR . 'PBC' .
    DIRECTORY_SEPARATOR . 'Tests' . DIRECTORY_SEPARATOR . 'Data' . DIRECTORY_SEPARATOR . 'tests.conf.json'
);
