<?php
/**
 * TechDivision\Tests\TypeSafety\TypeSafetyTestClass
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 */

namespace TechDivision\Tests\TypeSafety;

/**
 * @package     TechDivision\Tests
 * @subpackage  TypeSafety
 * @copyright   Copyright (c) 2013 <info@techdivision.com> - TechDivision GmbH
 * @license     http://opensource.org/licenses/osl-3.0.php
 *              Open Software License (OSL 3.0)
 * @author      Bernhard Wick <b.wick@techdivision.com>
 */
class TypeSafetyTestClass {

    /**
     * @param   string  $string1
     * @param   string  $string2
     */
    public function iNeedStrings($string1, $string2)
    {

    }

    /**
     * @param   array   $array1
     * @param   array   $array2
     */
    public function iNeedArrays($array1, $array2)
    {

    }

    /**
     * @param   numeric $numeric
     */
    public function iNeedNumeric($numeric)
    {

    }

    /**
     * @return string
     */
    public function iReturnAString($result = 'test')
    {
        return $result;
    }

    /**
     * @return array
     */
    public function iReturnAnArray($result = array('golem', 'clay'))
    {
        return $result;
    }

    /**
     * @return int
     */
    public function iReturnAnInt($result = 42)
    {
        return $result;
    }
} 