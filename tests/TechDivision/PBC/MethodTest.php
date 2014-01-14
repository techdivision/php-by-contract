<?php

require_once 'PHPUnit/Autoload.php';

require_once __DIR__ . "/../../../src/TechDivision/PBC/Bootstrap.php";


class MethodTest extends PHPUnit_Framework_TestCase
{

    private $magicMethodTestClass;

    /**
     * Check if we can cope with the magic methods from MagicMethodTestClass
     */
    public function testMagicMethod()
    {
        $this->magicMethodTestClass = 
            new \TechDivision\Tests\Method\MagicMethodTestClass();
    }
}
