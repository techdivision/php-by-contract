<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 22.08.13
 * Time: 18:29
 * To change this template use File | Settings | File Templates.
 */

require_once 'PHPUnit/Autoload.php';

require_once __DIR__ . "/../../../src/TechDivision/PBC/Bootstrap.php";

class InheritanceTest extends PHPUnit_Framework_TestCase {

    public function testInheritance()
    {
        $testClass = new ChildTestClass();


        // These should fail
        $e = null;
        try {

            $testClass->pop();

        } catch (Exception $e) {}

        // Did we get the right $e?
        $this->assertInstanceOf("TechDivision\\PBC\\Exceptions\\BrokenPreconditionException", $e);

    }

}
