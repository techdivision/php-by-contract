<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 19.06.13
 * Time: 14:59
 * To change this template use File | Settings | File Templates.
 */

// Do the bootstrapping, so we will use our library
require __DIR__ . "/../../../src/TechDivision/PBC/Bootstrap.php";

class BasicTest extends PHPUnit_Framework_TestCase
{
    /**
     *
     */
    public function testInvariantBreaks()
    {
        // Get the object to test
        $test = new BasicTestClass();

        // This one should not break
        $test->iDontBreakTheInvariant();

        $e = null;
        try {

            $test->iBreakTheInvariant();

        } catch (Exception $e) {}

        // Did we get the right $e?
        $this->assertInstanceOf("TechDivision\\PBC\\Exceptions\\BrokenInvariantException", $e);
    }

    /**
     *
     */
    public function testParamTyping()
    {
        // Get the object to test
        $test = new BasicTestClass();

        // These tests should all be successful
        $test->stringToArray("null");
        $test->concatSomeStuff(17, 'test', new \Exception());
        $test->stringToWelcome('stranger');


        // These should all fail
        $e = null;
        try {

            $test->stringToArray(13);

        } catch (Exception $e) {}

        // Did we get the right $e?
        $this->assertInstanceOf("TechDivision\\PBC\\Exceptions\\BrokenPreConditionException", $e);

        $e = null;
        try {

            $test->concatSomeStuff("26", array(), new \Exception());

        } catch (Exception $e) {}

        // Did we get the right $e?
        $this->assertInstanceOf("TechDivision\\PBC\\Exceptions\\BrokenPreConditionException", $e);

        $e = null;
        try {

            $test->stringToWelcome(34);

        } catch (Exception $e) {}

        // Did we get the right $e?
        $this->assertInstanceOf("TechDivision\\PBC\\Exceptions\\BrokenPreConditionException", $e);
    }

    /**
     *
     */
    public function testResultTyping()
    {

    }

    /**
     *
     */
    public function testParamContent()
    {

    }

    /**
     *
     */
    public function testResultContent()
    {

    }
}