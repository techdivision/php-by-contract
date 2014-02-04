<?php
// Load PBCs bootstrapping
require_once '${php-target.dir}/${codepool}/${namespace}/${module}/src/TechDivision/PBC/Bootstrap.php';

// Load the test config file
$config = TechDivision\PBC\Config::getInstance();
$config->load(__DIR__ . DIRECTORY_SEPARATOR . 'TechDivision' . DIRECTORY_SEPARATOR . 'PBC' .
    DIRECTORY_SEPARATOR . 'Tests' . DIRECTORY_SEPARATOR . 'Data' . DIRECTORY_SEPARATOR . 'tests.conf.json');