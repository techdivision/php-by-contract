<?php
/**
 * TechDivision\PBC\Tests\InheritanceTest
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
class InheritanceTest extends \PHPUnit_Framework_TestCase
{

    public function testInheritance()
    {
        $testClass = new Data\ChildTestClass();


        // These should fail
        $e = null;
        try {

            $testClass->pop();

        } catch (\Exception $e) {
        }

        // Did we get the right $e?
        $this->assertInstanceOf("TechDivision\\PBC\\Exceptions\\BrokenPreconditionException", $e);

    }
}
