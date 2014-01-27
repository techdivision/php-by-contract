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

class GeneratorTest extends PHPUnit_Framework_TestCase
{

    /**
 *
 */
    public function testPhpTag()
    {
        $e = null;
        try {

            $tagPlacementTestClass = new \TechDivision\Tests\Generator\TagPlacementTestClass();

        } catch (Exception $e) {
        }

        // Did we get the right $e?
        $this->assertNull($e);
    }
}