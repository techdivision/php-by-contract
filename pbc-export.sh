#!/usr/bin/php
<?php

// We will need the Exporter class later on
require_once __DIR__ . '/src/TechDivision/PBC/Exporter.php';

// Set of variables we need as a minimum
$target = '';
$source = '';

// All available options
$allowedOptions = array('--target', '--source');

// Lets start with analysing what we got
$foundArguments = array();
foreach($argv as $argument) {

    // check of we got an option
    foreach($allowedOptions as $allowedOption) {

        // If we recognize an allowed option we safe it for later
        if (strpos($argument, $allowedOption) !== false) {

            $foundArguments[$allowedOption] = str_replace(array($allowedOption, '='), '', $argument);
        }
    }
}

// If we didn't get enough we can quit
if (count($foundArguments) < 2) {

    printUsage();
    exit;
}

// If we come that far we have to have a closer look at what we got
foreach($foundArguments as $option => $foundArgument) {

    $tmp = str_replace('--', '', $option);
    if (isset($$tmp)) {

        $$tmp = $foundArgument;
    }
}

// Lets check if we got everything we need
if (empty($target) || empty($source)) {

    printUsage();
    exit;
}

// We are still here, so everything seems to be going as planned.
// Do the export!
try {

    $exporter = new TechDivision\PBC\Exporter();
    $exporter->export($source, $target);

} catch (Exception $e) {

    echo $e->getMessage() . '
';
}

/**
 *  END OF SCRIPTED PART. SOME HELPER FUNCTIONS BELOW.
 */

/**
 * Prints the usage hint of this script
 */
function printUsage()
{
    echo 'usage is: <PATH_TO_SCRIPT>' . basename(__FILE__) . ' --source=<SOURCE_PATH> --target=<TARGET_PATH>
';
}

// Final exit to end it all
exit;