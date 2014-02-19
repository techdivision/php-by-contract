<?php
/**
 * File containing the Formatting class
 *
 * PHP version 5
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Utils
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 *             Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */

namespace TechDivision\PBC\Utils;

/**
 * TechDivision\PBC\Utils\Formatting
 *
 * Will provide basic formatting for same needed conversions of strings in special ways
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Utils
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 *             Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */
class Formatting
{

    /**
     * Will break up any path into a canonical form like realpath(), but does not require the file to exist.
     *
     * @param string $path The path to normalize
     *
     * @return mixed
     */
    public function normalizePath($path)
    {
        return array_reduce(
            explode('/', $path),
            function ($a, $b) {
                if ($a === 0) {
                    $a = "/";
                }

                if ($b === "") {
                    return $a;
                }

                if ($b === ".") {
                    return str_replace(DIRECTORY_SEPARATOR . "Utils", "", __DIR__);
                }

                if ($b === "..") {
                    return dirname($a);
                }

                return preg_replace("/\/+/", "/", "$a/$b");
            }
        );
    }

    /**
     * Converts a string by escaping all regex relevant characters in it.
     *
     * @param string $string The string to convert
     *
     * @return string|array
     */
    public function toRegex($string)
    {
        return str_replace(
            array('$', '(', ')', '*', '[', ']', ' ', '/'),
            array('\$', '\(', '\)', '\*', '\[', '\]', '\s*', '\/'),
            $string
        );
    }
}
