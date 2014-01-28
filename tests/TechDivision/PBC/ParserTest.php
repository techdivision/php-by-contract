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

use TechDivision\PBC\Config;

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

        // Get the object to test
        $annotationTestClass = new \TechDivision\Tests\Parser\AnnotationTestClass();

        $e = null;
        try {

            $annotationTestClass->typeCollectionReturn(array(new Exception(), new Exception(), new Exception()));

        } catch (Exception $e) {
        }

        // Did we get the right $e?
        $this->assertNull($e);

        $e = null;
        try {

            $annotationTestClass->typeCollectionReturn(array(new Exception(), 'failure', new Exception()));

        } catch (Exception $e) {
        }

        // Did we get the right $e?
        $this->assertInstanceOf("TechDivision\\PBC\\Exceptions\\BrokenPostconditionException", $e);

        $e = null;
        try {

            $annotationTestClass->orCombinator(new Exception());
            $annotationTestClass->orCombinator(null);

        } catch (Exception $e) {
        }

        // Did we get the right $e?
        $this->assertNull($e);

        $e = null;
        try {

            $annotationTestClass->orCombinator(array());

        } catch (Exception $e) {
        }

        // Did we get the right $e?
        $this->assertInstanceOf("TechDivision\\PBC\\Exceptions\\BrokenPreconditionException", $e);
    }

    /**
     *
     */
    public function testMethodParsing()
    {
        $e = null;
        try {

            $methodTestClass = new \TechDivision\Tests\Parser\MethodTestClass();

        } catch (Exception $e) {
        }

        // Did we get the right $e?
        $this->assertNull($e);
    }

    /**
     *
     */
    public function testRegexMapping()
    {
        // We have to load the config for regular expressions in the project dirs
        $config = Config::getInstance();
        $config = $config->load(__DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'RegexTest' .
            DIRECTORY_SEPARATOR . 'regextest.conf.json');

        $e = null;
        try {

            $regexTestClass1 = new \TechDivision\Tests\Parser\RegexTestClass1();

        } catch (Exception $e) {
        }

        // Did we get the right $e?
        $this->assertNull($e);

        $e = null;
        try {

            $regexTestClass2 = new \TechDivision\Tests\Parser\RegexTestClass2();

        } catch (Exception $e) {
        }

        // Did we get the right $e?
        $this->assertNull($e);

        $e = null;
        try {

            $regexTestClass = new \TechDivision\Tests\Parser\RegexTestClass();

        } catch (Exception $e) {
        }

        // Did we get the right $e?
        $this->assertNull($e);
    }
}