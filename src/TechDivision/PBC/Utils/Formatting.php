<?php
/**
 * TechDivision\PBC\Utils\Formatting
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 */
namespace TechDivision\PBC\Utils;

/**
 * @package     TechDivisionPBCUtils
 * @subpackage  
 * @copyright   Copyright (c) 2013 <info@techdivision.com> - TechDivision GmbH
 * @license     http://opensource.org/licenses/osl-3.0.php
 *              Open Software License (OSL 3.0)
 * @author      Bernhard Wick <b.wick@techdivision.com>
 */
class Formatting
{

    /**
     * Will break up any path into a canonical form like realpath(), but does not require the file to exist.
     *
     * @param $path
     *
     * @return mixed
     */
    public function normalizePath($path)
    {
        return array_reduce(
            explode('/', $path),
            create_function(
                '$a, $b',
                '
                           if($a === 0)
                               $a = "/";

                           if($b === "")
                               return $a;

                           if($b === ".")
                               return str_replace(DIRECTORY_SEPARATOR . "Utils", "", __DIR__);

                           if($b === "..")
                               return dirname($a);

                           return preg_replace("/\/+/", "/", "$a/$b");
                       '
            ),
            0
        );
    }
}
