<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 23.07.13
 * Time: 17:29
 * To change this template use File | Settings | File Templates.
 */

// Do the bootstrapping, so we will use our library
require __DIR__ . "/../../../src/TechDivision/PBC/Bootstrap.php";

use TechDivision\Example\Servlets\IndexServlet;

class RealTest extends \PHPUnit_Framework_TestCase {

    /**
     *
     */
    public function testInstantiation()
    {
        // Get the object to test
        new IndexServlet();
    }
}
