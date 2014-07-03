<?php
/**
 * File containing the TypedListList class
 *
 * PHP version 5
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Entities
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */

namespace TechDivision\PBC\Entities\Lists;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'AbstractTypedList.php';

/**
 * TechDivision\PBC\Entities\Lists\TypedListList
 *
 * A typed list which is able to contain typed lists by itself
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Entities
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */
class TypedListList extends AbstractTypedList
{
    /**
     * Default constructor
     */
    public function __construct()
    {
        $this->itemType = 'TechDivision\PBC\Interfaces\TypedListInterface';
    }

    /**
     * Overwritten implementation of count() which is able to determine the count of contained lists
     * as a whole.
     *
     * @param bool $countChildren Should we count the entries of the contained lists?
     *
     * @return int
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
