<?php
/**
 * TechDivision\PBC\Tests\MethodTest
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 */
namespace TechDivision\PBC\Tests;

require_once 'PHPUnit/Autoload.php';
require_once __DIR__ . "/../../../../src/TechDivision/PBC/Bootstrap.php";

/**
 * @package     TechDivision\PBC
 * @subpackage  Tests
 * @copyright   Copyright (c) 2013 <info@techdivision.com> - TechDivision GmbH
 * @license     http://opensource.org/licenses/osl-3.0.php
 *              Open Software License (OSL 3.0)
 * @author      Bernhard Wick <b.wick@techdivision.com>
 */
class MethodTest extends \PHPUnit_Framework_TestCase
{

    private $magicMethodTestClass;

    /**
     * Check if we can cope with the magic methods from MagicMethodTestClass
     */
    public function testMagicMethod()
    {
        $this->magicMethodTestClass =
            new Data\MagicMethodTestClass();
    }

    /**
     *
     */
    public function testMagicConstantSubstitution()
    {
        $methodTestClass = new Data\MethodTestClass();

        $e = null;
        try {

            $dir = $methodTestClass->returnDir();

        } catch (\Exception $e) {
        }

        // Did we get the right $e and right dir?
        $this->assertNull($e);
        $this->assertEquals($dir, __DIR__ . DIRECTORY_SEPARATOR . 'Data');

        $e = null;
        try {

            $file = $methodTestClass->returnFile();

        } catch (\Exception $e) {
        }

        // Did we get the right $e and right file?
        $this->assertNull($e);
        $this->assertEquals(
            $file,
            __DIR__ . DIRECTORY_SEPARATOR . 'Data' . DIRECTORY_SEPARATOR . 'MethodTestClass.php'
        );
    }
}
