<?php
/**
 * File containing the AbstractFilter class
 *
 * PHP version 5
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage StreamFilters
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */

namespace TechDivision\PBC\StreamFilters;

use TechDivision\PBC\Interfaces\StreamFilterInterface;

/**
 * TechDivision\PBC\StreamFilters\AbstractFilter
 *
 * This abstract class provides a clean parent class for custom stream filters
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage StreamFilters
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */
abstract class AbstractFilter extends \php_user_filter implements StreamFilterInterface
{
    /**
     * @var string $filtername Name of the filter (done as seen in \php_user_filter class)
     * @link http://www.php.net/manual/en/class.php-user-filter.php
     */
    public $filtername = __CLASS__;
}
