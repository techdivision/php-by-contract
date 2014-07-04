<?php
/**
 * File containing the BasicTest class
 *
 * PHP version 5
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Tests
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 *             Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */

namespace TechDivision\PBC\Tests;

use TechDivision\PBC\Config;

/**
 * TechDivision\PBC\Tests\BasicTest
 *
 * This test will test the configuration class TechDivision\PBC\Config
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Tests
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 *             Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */
class ConfigTest extends \PHPUnit_Framework_TestCase
{
    /**
     * The config instance
     *
     * @var \TechDivision\PBC\Config $config
     */
    protected $config = array();

    /**
     * Set up the test
     */
    public function setUp()
    {
        $this->config = Config::getInstance();
    }

    /**
     * Test the static getInstance() method
     *
     * @return void
     */
    public function testGetInstance()
    {
        $this->assertInstanceOf('\TechDivision\PBC\Config', Config::getInstance());
    }

    /**
     * Test the setValue() method
     *
     * @return void
     */
    public function testSetValue()
    {
        // Set a simple value and test if it got set
        $this->config->setValue('environment', 'testing');
        $this->assertEquals('testing', $this->config->getValue('environment'));

        // Set a more complex value and test if it got set
        $this->config->setValue('autoloader/dirs', array(1, 2, 3));
        $this->assertEquals(array(1, 2, 3), $this->config->getValue('autoloader/dirs'));

        // Test if both values got stored in the singleton
        $tmpConfig = Config::getInstance();
        $this->assertEquals('testing', $tmpConfig->getValue('environment'));
        $this->assertEquals(array(1, 2, 3), $tmpConfig->getValue('autoloader/dirs'));
    }
}
