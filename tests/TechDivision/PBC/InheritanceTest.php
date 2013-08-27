<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 22.08.13
 * Time: 18:29
 * To change this template use File | Settings | File Templates.
 */

// Do the bootstrapping, so we will use our library
require __DIR__ . "/../../../src/TechDivision/PBC/Bootstrap.php";

class InheritanceTest extends PHPUnit_Framework_TestCase {

    public function testInheritance()
    {
        $testClass = new ChildTestClass();
    }

}
