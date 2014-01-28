<?php
/**
 * TechDivision\PBC\Interfaces\MapInterface
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 */

namespace TechDivision\PBC\Interfaces;

use TechDivision\PBC\Entities\Definitions\Structure;

/**
 * @package     TechDivision\PBC
 * @subpackage  Interfaces
 * @copyright   Copyright (c) 2013 <info@techdivision.com> - TechDivision GmbH
 * @license     http://opensource.org/licenses/osl-3.0.php
 *              Open Software License (OSL 3.0)
 * @author      Bernhard Wick <b.wick@techdivision.com>
 */
interface MapInterface
{
    /**
     * Will return all entries within a map. If needed only entries of contracted
     * structures will be returned.
     *
     * @param bool $contracted
     *
     * @return mixed
     */
    public function getEntries($contracted = false);

    /**
     * Will add a structure entry to the map.
     *
     * @param Structure $structure
     *
     * @return bool
     */
    public function add(Structure $structure);

    /**
     * @param $identifier
     *
     * @return bool
     */
    public function entryExists($identifier);

    /**
     * @param Structure $structure
     *
     * @return mixed
     */
    public function update(Structure $structure = null);

    /**
     * Will return the entry specified by it's identifier.
     * If none is found, false will be returned.
     *
     * @param $identifier
     *
     * @return bool|Structure
     */
    public function getEntry($identifier);

    /**
     * Checks if the entry for a certain structure is current if one was specified.
     * If not it will check if the whole map is current.
     *
     * @param null|string $identifier
     *
     * @return  bool
     */
    public function isCurrent($identifier = null);

    /**
     * Will return an array of all classes which are stored in this map.
     *
     * @param string $type
     *
     * @return array
     */
    public function getIdentifiers($type = null);

    /**
     * Will return an array of all files which are stored in this map.
     * Will include the full path if $fullPath is true.
     *
     * @param   $fullPath
     *
     * @return  array
     */
    public function getFiles($fullPath = true);

    /**
     * Removes an entry from the map of structures.
     *
     * @param $identifier
     *
     * @return bool
     */
    public function remove($identifier);
}
