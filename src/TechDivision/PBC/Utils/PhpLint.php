<?php
/**
 * File containing the PhpLint class
 *
 * PHP version 5
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Utils
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */

namespace TechDivision\PBC\Utils;

/**
 * TechDivision\PBC\Utils\PhpLint
 *
 * Will provide a basic linting function for php code
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Utils
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */
class PhpLint
{

    /**
     * Will remove any PHP start or end tags from the code.
     *
     * @param string $code The to strip from the tags
     *
     * @return mixed
     */
    protected function removePhpTags($code)
    {
        return str_replace(array('<?php', '?>', '<?', '<?='), '', $code);
    }

    /**
     * Will check if code is PHP syntax conform.
     *
     * @param string $code The code to check for syntax errors
     *
     * @throws \Exception
     *
     * @return boolean
     */
    public function check($code)
    {
        // Save the current error reporting level and set level to 0.
        // We would get errors shown to the use if we did not do that.
        $level = error_reporting();
        error_reporting(0);

        try {

            // Eval the passed code inside a never entered if clause.
            // That way we can make sure to not execute any bogus code
            $result = eval('if (false){' . $this->removePhpTags($code) . '}');

            // eval does not return true if there was no error, but we want to
            if ($result === null) {

                $result = true;
            }

        } catch (\Exception $e) {

            // Set the error reporting to the intended level and fail
            error_reporting($level);
            throw $e;
        }

        // Reset the error reporting level to the original value
        error_reporting($level);

        // Return our result
        return $result;
    }
}
