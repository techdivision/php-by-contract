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
     * Test the static getInstance() method
     *
     * @return void
     */
    public function testGetInstance()
    {
        $this->assertInstanceOf('\TechDivision\PBC\Config', new Config());
    }

    /**
     * Test the setValue() method
     *
     * @return void
     */
    public function testSetValue()
    {
        // Get our config
        $config = new Config();

        // Set a simple value and test if it got set
        $config->setValue('environment', 'testing');
        $this->assertEquals('testing', $config->getValue('environment'));

        // Set a more complex value and test if it got set
        $config->setValue('autoloader/dirs', array(1, 2, 3));
        $this->assertEquals(array(1, 2, 3), $config->getValue('autoloader/dirs'));
    }
}
