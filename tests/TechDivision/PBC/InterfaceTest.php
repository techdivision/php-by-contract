<?php

require_once 'PHPUnit/Autoload.php';

require_once __DIR__ . "/../../../src/TechDivision/PBC/Bootstrap.php";

use \Test\InterfaceTest\InterfaceClass;

class InterfaceTest extends PHPUnit_Framework_TestCase
{

    public function testInstantiation()
    {
        $interfaceClass = new InterfaceClass();
    }

    public function testStackUsage()
    {
        $interfaceClass = new InterfaceClass();

        $someStrings = array('sdfsafsf', 'rzutrzutfzj', 'OUHuISGZduisd0', 'skfse', 'd', 'fdghdfg', 'srfxcf');

        // push the strings into the stack
        foreach ($someStrings as $someString) {

            $interfaceClass->push($someString);
        }
        // and pop some of them again
        $interfaceClass->pop();
        $interfaceClass->pop();
        $interfaceClass->pop();
        $interfaceClass->pop();
        $interfaceClass->pop();
        $interfaceClass->pop();

        $this->assertEquals($interfaceClass->peek(), $interfaceClass->pop());
    }

    public function testPbcUsage()
    {
        $interfaceClass = new InterfaceClass();
    }
}
