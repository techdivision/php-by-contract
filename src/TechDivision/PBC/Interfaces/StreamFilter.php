<?php
/**
 * TechDivision\PBC\Interfaces\StreamFilter
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 */

namespace TechDivision\PBC\Interfaces;

/**
 * Interface StreamFilter
 */
interface StreamFilter
{
    public function getFilterOrder();

    public function dependenciesMet();
}