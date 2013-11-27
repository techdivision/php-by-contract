<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 19.06.13
 * Time: 14:59
 * To change this template use File | Settings | File Templates.
 */

require_once 'PHPUnit/Autoload.php';

require_once __DIR__ . "/../../../src/TechDivision/PBC/Bootstrap.php";

class ParserTest extends PHPUnit_Framework_TestCase
{
    /**
     *
     */
    public function testAnnotationParsing()
    {
        // Get the object to test
        $annotationTestClass = new \TechDivision\Tests\Parser\AnnotationTestClass();

        $e = null;
        try {

            $annotationTestClass->typeCollection(array(new Exception(), new Exception(), new Exception()));

        } catch (Exception $e) {
        }

        // Did we get the right $e?
        $this->assertNull($e);

        $e = null;
        try {

            $annotationTestClass->typeCollection(array(new Exception(), 'failure', new Exception()));

        } catch (Exception $e) {
        }

        // Did we get the right $e?
        $this->assertInstanceOf("TechDivision\\PBC\\Exceptions\\BrokenPreconditionException", $e);
    }
}