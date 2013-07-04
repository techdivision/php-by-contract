<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 20.06.13
 * Time: 17:37
 * To change this template use File | Settings | File Templates.
 */

require "../../../../src/TechDivision/PBC/Bootstrap.php";

$test = new \TestBert();
$test->stringToArray("null");
$test->concatSomeStuff(27, 'test', new \Exception());
//$test->iBreakTheInvariant();
