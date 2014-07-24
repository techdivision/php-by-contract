<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @category   Php-by-contract
 * @package    TechDivision_PBC
 * @subpackage Tests
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */

namespace TechDivision\PBC\Tests\Functional;

use TechDivision\PBC\Config;
use TechDivision\PBC\StructureMap;

/**
 * TechDivision\PBC\Tests\Functional\StructureMapTest
 *
 * Some functional tests for the
 *
 * @category   Php-by-contract
 * @package    TechDivision_PBC
 * @subpackage Tests
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */
class StructureMapTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Directories that contain the data needed for tests
     *
     * @var array $dataDirs
     */
    protected $dataDirs;

    /**
     * Instance of a prepared structure map.
     * Create your own if you
     *
     * @var \TechDivision\PBC\StructureMap $structureMap
     */
    protected $structureMap;

    /**
     * Set upt the test environment
     */
    public function setUp()
    {
        $this->dataDirs = array(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Data');

        // get the objects we need
        $config = new Config();
        $config->setValue('autoload/dirs', $this->dataDirs);
        $config->setValue('enforcement/dirs', array());
        $this->structureMap = new StructureMap(
            $config->getValue('autoload/dirs'),
            $config->getValue('enforcement/dirs'),
            $config
        );

        // fill the map
        $this->structureMap->fill();
    }

    /**
     * Will test if classes with underscores in their name can get processed the right way
     *
     * @return void
     */
    public function testWithUnderscoredClass()
    {
        // test if we have the entry for the underscored class
        $this->assertTrue($this->structureMap->entryExists('Random\Test\NamespaceName\Underscored_Class'));
    }

    /**
     * Will test if classes with huge class doc comments can be picked up correctly
     *
     * @return void
     */
    public function testWithHugeClassDocBlockClass()
    {
        // test if we have the entry for the underscored class
        $this->assertTrue($this->structureMap->entryExists('Random\Test\NamespaceName\HugeClassDocBlockClass'));
    }
}
