<?php
/**
 * TechDivision\PBC\CacheMap
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 */
namespace TechDivision\PBC;

use TechDivision\PBC\Entities\Definitions\Structure;

/**
 * @package     TechDivision\PBC
 * @copyright   Copyright (c) 2013 <info@techdivision.com> - TechDivision GmbH
 * @license     http://opensource.org/licenses/osl-3.0.php
 *              Open Software License (OSL 3.0)
 * @author      Bernhard Wick <b.wick@techdivision.com>
 */
class CacheMap extends StructureMap
{
    /**
     * Will add a structure entry to the map.
     *
     * @param Structure $structure
     *
     * @return bool
     */
    public function add(Structure $structure)
    {
        // The the entry
        $this->map[$structure->getIdentifier()] = array(
            'cTime' => $structure->getCTime(),
            'identifier' => $structure->getIdentifier(),
            'path' => $structure->getPath(),
            'type' => $structure->getType()
        );

        // Persist the map
        return $this->save();
    }

    /**
     * @param $identifier
     *
     * @return bool
     */
    public function entryExists($identifier)
    {
        return isset($this->map[$identifier]);
    }

    public function update(Structure $structure = null)
    {

    }

    /**
     * Will return the entry specified by it's identifier.
     * If none is found, false will be returned.
     *
     * @param $identifier
     *
     * @return bool|Structure
     */
    public function getEntry($identifier)
    {
        if (isset($this->map[$identifier])) {

            // We got it, lets biuld a structure object
            $entry = $this->map[$identifier];
            $structure = new Structure(
                $entry['cTime'],
                $entry['identifier'],
                $entry['path'],
                $entry['type']
            );

            // Return the structure DTO
            return $structure;

        } else {

            return false;
        }
    }

    /**
     * Checks if the entry for a certain structure is current if a one was specified.
     * If not it will check if the whole map is current.
     *
     * @param null|string $structure
     *
     * @return  bool
     */
    public function isCurrent($identifier = null)
    {
        // Our result
        $result = false;

        // If we got a class name
        if ($identifier !== null && isset($this->map[$identifier])) {

            // Is the stored file time the same as directly from the file?
            if ($this->map[$identifier]['cTime'] === filectime($this->map[$identifier]['path'])) {

                return true;
            }
        }

        // We got no class name, check the whole thing
        if ($identifier === null) {
            /*
                        // Get an iterator over all files in the root path
                        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->rootPath),
                            RecursiveIteratorIterator::SELF_FIRST);

                        foreach($objects as $name => $object){
                            echo "$name\n";
                        }*/
            return true;
        }

        // Still here? That seems wrong.
        return $result;
    }

    /**
     * Will return an array of all classes which are stored in this map.
     *
     * @param string $type
     *
     * @return array
     */
    public function getIdentifiers($type = null)
    {
        if ($type === null) {

            return array_keys($this->map);

        } else {

            // Collect the data.
            $result = array();
            foreach ($this->map as $identifier => $file) {

                if ($file['type'] === $type) {

                    $result[] = $identifier;
                }
            }

            return $result;
        }
    }

    /**
     * Will return an array of all files which are stored in this map.
     * Will include the full path if $fullPath is true.
     *
     * @param   $fullPath
     *
     * @return  array
     */
    public function getFiles($fullPath = true)
    {
        // What information do we have to get?
        if ($fullPath === true) {

            $method = 'realpath';

        } else {

            $method = 'dirname';
        }

        // Collect the data.
        $result = array();
        foreach ($this->map as $file) {

            $result[] = $method($file['path']);
        }

        return $result;
    }

    /**
     * Removes an entry from the map of structures.
     *
     * @param $identifier
     *
     * @return bool
     */
    public function remove($identifier)
    {
        if (is_string($identifier) && isset($this->map[$identifier])) {

            unset($this->map[$identifier]);

            return true;

        } else {

            return false;
        }

    }
}
