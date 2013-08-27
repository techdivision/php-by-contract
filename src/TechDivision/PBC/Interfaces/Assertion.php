<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 04.07.13
 * Time: 11:03
 * To change this template use File | Settings | File Templates.
 */

namespace TechDivision\PBC\Interfaces;

interface Assertion
{
    public function getInvertString();

    public function getString();

    public function invert();

    public function isInverted();

    public function isValid();
}