<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 23.07.13
 * Time: 17:29
 * To change this template use File | Settings | File Templates.
 */

require_once 'PHPUnit/Autoload.php';

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . "/../../../src/TechDivision/PBC/Bootstrap.php";

class StackTest extends \PHPUnit_Framework_TestCase {

    /**
     *
     */
    public function testBuild()
    {
        // Get the object to test
        $stackSale = new Wicked\salesman\Sales\Stack\StackSale();
        $stackSale->sell();
    }
}
