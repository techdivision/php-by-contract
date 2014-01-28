<?php
/**
 * TechDivision\PBC\Tests\Data\BasicTestClass
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 */
namespace TechDivision\PBC\Tests\Data;

use TechDivision\PBC\Entities\Assertion;
use TechDivision\PBC\Entities\AssertionList;
use TechDivision\PBC\Entities\ClassDefinition;
use TechDivision\PBC\Entities\FunctionDefinition;
use TechDivision\PBC\Entities\FunctionDefinitionList;
use TechDivision\PBC\Entities\ScriptDefinition;

/**
 * @package     TechDivision\PBC
 * @subpackage  Tests
 * @copyright   Copyright (c) 2013 <info@techdivision.com> - TechDivision GmbH
 * @license     http://opensource.org/licenses/osl-3.0.php
 *              Open Software License (OSL 3.0)
 * @author      Bernhard Wick <b.wick@techdivision.com>
 *
 * @invariant $this->motd === 'Welcome stranger!'
 */
final class BasicTestClass
{
    /**
     * @var string
     */
    private $motd = 'Welcome stranger!';

    /**
     * @requires $param1 < 27 && $param1 > 18 or $param1 === 17
     *
     * @param integer   $param1
     * @param string    $param2
     * @param \Exception $param3
     *
     * @return string
     */
    public function concatSomeStuff($param1, $param2, \Exception $param3)
    {
        return (string)$param1 . $param2 . $param3->getMessage();
    }

    /**
     * @requires $param1 == 'null'
     *
     * @param string $param1
     *
     * @return array
     */
    public function stringToArray($param1)
    {
        return array($param1);
    }

    /**
     * @requires $ourString === 'stranger'
     *
     * @param string $ourString
     *
     * @ensures $pbcResult === 'Welcome stranger'
     *
     * @return string
     */
    public function stringToWelcome($ourString)
    {
        return "Welcome " . $ourString;
    }

    /**
     *
     */
    public function iBreakTheInvariant()
    {
        $this->motd = 'oh no!!!';
    }

    /**
     *
     */
    public function iDontBreakTheInvariant()
    {
        // We will break the invariant here
        $this->invariantBreaker();

        // and do some stuff here
        $iAmUseless = $this->motd;

        // now we repair the invariant again
        $this->invariantRepair();

        // We return something just for the hell of it
        return $iAmUseless;
    }

    /**
     * Will break the invariant
     */
    private function invariantBreaker($test = array())
    {
        $this->motd = 'We are doomed!';
    }

    /**
     * Will repair the invariant
     */
    private function invariantRepair()
    {
        $this->motd = 'Welcome stranger!';
    }
}
