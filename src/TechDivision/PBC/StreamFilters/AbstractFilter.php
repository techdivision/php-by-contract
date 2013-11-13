<?php
/**
 * TechDivision\PBC\StreamFilters\AbstractFilter
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 */

namespace TechDivision\PBC\StreamFilters;

use TechDivision\PBC\Exceptions\GeneratorException;
use TechDivision\PBC\Interfaces\StreamFilter;

/**
 * @package     TechDivision\PBC
 * @subpackage  StreamFilters
 * @copyright   Copyright (c) 2013 <info@techdivision.com> - TechDivision GmbH
 * @license     http://opensource.org/licenses/osl-3.0.php
 *              Open Software License (OSL 3.0)
 * @author      Bernhard Wick <b.wick@techdivision.com>
 */
abstract class AbstractFilter extends \php_user_filter implements StreamFilter
{
    /**
     * @var string
     */
    public $filtername = __CLASS__;

    /**
     * @return string
     */
    public function getFilterName()
    {
        return $this->filterName;
    }
}