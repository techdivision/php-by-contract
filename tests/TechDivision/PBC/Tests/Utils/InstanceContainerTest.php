<?php
/**
 * File containing the InstanceContainerTest class
 *
 * PHP version 5
 *
 * @category   Php-by-contract
 * @package    PBC
 * @subpackage Tests
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 *             Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */

namespace TechDivision\PBC\Tests\Utils;

use TechDivision\PBC\Utils\InstanceContainer;

/**
 * TechDivision\PBC\Tests\Utils\InstanceContainerTest
 *
 * This test will test the configuration class TechDivision\PBC\Config
 *
 * @category   Php-by-contract
 * @package    PBC
 * @subpackage Tests
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 *             Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */
class InstanceContainerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Container instance to be used for tests that do not rely on the static stay
     *
     * @var \TechDivision\PBC\Utils\InstanceContainer $container
     */
    protected $container;

    /**
     * Set one initial value into the container so we can test the getOffset method
     *
     * @return void
     */
    public function setUp()
    {
        $this->container = new InstanceContainer();
        $this->container[__CLASS__] = 'value';
    }

    /**
     * Test setOffset method
     *
     * @return void
     *
     * @depends testGetOffset
     */
    public function testSetOffset()
    {
        $container = new InstanceContainer();
        $container[__METHOD__] = 'value';
        $this->assertEquals($container[__METHOD__], 'value');
    }

    /**
     * Test getOffset method
     *
     * @return void
     */
    public function testGetOffset()
    {
        $this->assertEquals($this->container[__CLASS__], 'value');
    }

    /**
     * Test if static stay of values works
     *
     * @return void
     *
     * @depends testSetOffset
     * @depends testGetOffset
     */
    public function testStaticStay()
    {
        $container = new InstanceContainer();
        $container[__METHOD__] = 'value';

        $tmpContainer = new InstanceContainer();
        $this->assertEquals($tmpContainer[__METHOD__], 'value');
    }
}
