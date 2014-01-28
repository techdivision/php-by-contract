<?php
/**
 * TechDivision\PBC\Utils\PhpLint
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 */
namespace TechDivision\PBC\Utils;

/**
 * @package     TechDivision\PBC
 * @subpackage  Utils
 * @copyright   Copyright (c) 2013 <info@techdivision.com> - TechDivision GmbH
 * @license     http://opensource.org/licenses/osl-3.0.php
 *              Open Software License (OSL 3.0)
 * @author      Bernhard Wick <b.wick@techdivision.com>
 */
class PhpLint
{

    /**
     * Will remove any PHP start or end tags from the code.
     *
     * @param $code
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
     * @param $code
     *
     * @return boolean
     * @throws \Exception
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
