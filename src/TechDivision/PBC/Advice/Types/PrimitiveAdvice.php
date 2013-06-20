<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 19.06.13
 * Time: 13:20
 * To change this template use File | Settings | File Templates.
 */

namespace TechDivision\PBC\Advice\Types;

use TechDivision\PBC\Advice\AdviceTypesInterface;

/**
 * Class PrimitiveAdvice
 * @package TechDivision\PBC\Advice\Types
 */
class PrimitiveAdvice implements AdviceTypesInterface{

    /**
     * @param $assert
     * @return bool
     */
    function isType($type, $assert)
    {
        // Lets check which function we would need to check for this type
        $function = 'is_' . strtolower($type);

        // If there is no core function to check for the given type
        if (function_exists($function) == false) {

            return false;
        }

        // Do the assertion and return the result
        return (boolean) $function($assert);
    }

}