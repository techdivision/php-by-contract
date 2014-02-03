<?php
/**
 * File containing the MethodTest class
 *
 * PHP version 5
 *
 * @category   php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Tests
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 *             Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */

namespace TechDivision\PBC\Tests;

/**
 * TechDivision\PBC\Tests\MethodTest
 *
 * Will test proper usage of magic functionality
 *
 * @category   php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Tests
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 *             Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */
class MethodTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var  $magicMethodTestClass Data class which we will run our tests on
     */
    private $magicMethodTestClass;

    /**
     * Check if we can cope with the magic methods from MagicMethodTestClass
     *
     * @return null
     */
    public function testMagicMethod()
    {
        $this->magicMethodTestClass =
            new Data\MagicMethodTestClass();
    }

    /**
     * Will test if the magic constants _DIR_ and _FILE_ get substituted correctly
     *
     * @return null
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
