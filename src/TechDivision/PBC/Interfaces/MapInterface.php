<?php
/**
 * File containing the MapInterface interface
 *
 * PHP version 5
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Interfaces
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 *             Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */

namespace TechDivision\PBC\Interfaces;

use TechDivision\PBC\Entities\Definitions\Structure;

/**
 * TechDivision\PBC\Interfaces\MapInterface
 *
 * An interface defining the functionality of any possible map class
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Interfaces
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 *             Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */
interface MapInterface
{
    /**
     * Will return all entries within a map. If needed only entries of contracted
     * structures will be returned.
     *
     * @param boolean $contracted Do we only want entries containing contracts?
     *
     * @return mixed
     */
    public function getEntries($contracted = false);

    /**
     * Will add a structure entry to the map.
     *
     * @param \TechDivision\PBC\Entities\Definitions\Structure $structure The structure to add
     *
     * @return bool
     */
    public function add(Structure $structure);

    /**
     * Do we have an entry for the given identifier
     *
     * @param string $identifier The identifier of the entry we try to find
     *
     * @return bool
     */
    public function entryExists($identifier);

    /**
     * Will update a given structure.
     * If the entry does not exist we will create it
     *
     * @param \TechDivision\PBC\Entities\Definitions\Structure $structure The structure to update
     *
     * @return void
     *
     * TODO implement this in the implementing classes
     */
    public function update(Structure $structure = null);

    /**
     * Will return the entry specified by it's identifier.
     * If none is found, false will be returned.
     *
     * @param string $identifier The identifier of the entry we try to find
     *
     * @return boolean|\TechDivision\PBC\Entities\Definitions\Structure
     */
    public function getEntry($identifier);

    /**
     * Checks if the entry for a certain structure is recent if one was specified.
     * If not it will check if the whole map is recent.
     *
     * @param null|string $identifier The identifier of the entry we try to find
     *
     * @return  boolean
     */
    public function isRecent($identifier = null);

    /**
     * Will return an array of all entry identifiers which are stored in this map.
     * We might filter by entry type
     *
     * @param string|null $type The type to filter by
     *
     * @return array
     */
    public function getIdentifiers($type = null);

    /**
     * Will return an array of all files which are stored in this map.
     * Will include the full path if $fullPath is true.
     *
     * @param boolean $fullPath Do we need the full path?
     *
     * @return  array
     */
    public function getFiles($fullPath = true);

    /**
     * Removes an entry from the map of structures.
     *
     * @param null|string $identifier The identifier of the entry we try to find
     *
     * @return boolean
     */
    public function remove($identifier);
}
