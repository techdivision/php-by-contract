<?php
/**
 * TechDivision\PBC\Entities\Lists\TypedListList
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 */

namespace TechDivision\PBC\Entities\Lists;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'AbstractTypedList.php';

/**
 * @package     TechDivision\PBC
 * @subpackage  Entities
 * @copyright   Copyright (c) 2013 <info@techdivision.com> - TechDivision GmbH
 * @license     http://opensource.org/licenses/osl-3.0.php
 *              Open Software License (OSL 3.0)
 * @author      Bernhard Wick <b.wick@techdivision.com>
 */
class TypedListList extends AbstractTypedList
{

    public function __construct()
    {
        $this->itemType = 'TechDivision\PBC\Interfaces\TypedListInterface';
    }

    /**
     * Overwritten implementation of count() which is able to determine the count of contained lists
     * as a whole.
     *
     * @param bool $countChildren
     *
     * @return int|void
     */
    public function count($countChildren = false)
    {
        // If we do not want the children to be counted we can use the parent's count() method
        if ($countChildren !== true) {

            return parent::count();
        }

        $counter = 0;
        foreach ($this->container as $item) {

            $counter += $item->count();
        }

        return $counter;
    }
}
