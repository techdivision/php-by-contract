<?php

namespace TechDivision\PBC;

use TechDivision\PBC\Entities\Definitions\Structure;

/**
 * Class StructureMap
 *
 * @package TechDivision\PBC
 */
class StructureMap
{
    /**
     * @var array
     */
    protected $map = array();

    /**
     * @var string
     */
    protected $rootPath;

    /**
     * @var string
     */
    protected $mapPath;

    /**
     * Directory where serialized maps are stored.
     *
     * @var string
     */
    const MAP_DIR = __DIR__;

    /**
     * @var array
     */
    protected $omittedPathes;

    /**
     * @param string $rootPath
     * @param array $omittedPathes
     */
    public function __construct($rootPath, $omittedPathes = array())
    {
        // Set the rootPath and calculate the path to the map file
        $this->rootPath = $rootPath;
        $this->mapPath = self::MAP_DIR . DIRECTORY_SEPARATOR . md5($rootPath);

        // Set the omitted pathes
        $this->omittedPathes = $omittedPathes;

        // Load the serialized map.
        // If there is none or it isn't current we will generate it anew.
        if (!$this->load()) {

            $this->generate();
        }
    }

    /**
     * Will add a structure entry to the map.
     *
     * @param Structure $structure
     * @return bool
     */
    public function add(Structure $structure)
    {
        // The the entry
        $this->map[$structure->getIdentifier()] = array('cTime' => $structure->getCTime(),
            'identifier' => $structure->getIdentifier(),
            'path' => $structure->getPath(),
            'type' => $structure->getType());

        // Persist the map
        return $this->save();
    }

    /**
     * @param $identifier
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
     * @return bool|Structure
     */
    public function getEntry($identifier)
    {
        if (is_string($identifier) && isset($this->map[$identifier])) {

            // We got it, lets biuld a structure object
            $entry = $this->map[$identifier];
            $structure = new Structure($entry['cTime'],
                $entry['identifier'],
                $entry['path'],
                $entry['type']);

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
     * @return bool
     */
    public function remove($identifier)
    {
        if (isset($this->map[$identifier])) {

            unset($this->map[$identifier]);
            return true;

        } else {

            return false;
        }

    }

    /**
     * Will generate the structure map within the specified root path.
     *
     * @return  bool
     */
    private function generate()
    {
        // Get an iterator over all files in the root path
        $directory = new \RecursiveDirectoryIterator($this->rootPath);
        $iterator = new \RecursiveIteratorIterator($directory);

        // Lets prepare the patter based on the existance of omitted pathes.
        if (!empty($this->omittedPathes)) {

            $pattern = '/[^' . str_replace('/', '\/', implode($this->omittedPathes, '|')) . ']\.php$/i';

        } else {

            $pattern = '/^.+\.php$/i';
        }

        $regex = new \RegexIterator($iterator, $pattern, \RecursiveRegexIterator::GET_MATCH);

        foreach ($regex as $file) {

            $identifier = $this->findIdentifier($file[0]);

            if ($identifier !== false) {

                $this->map[$identifier[1]] = array('cTime' => filectime($file[0]),
                    'identifier' => $identifier[1],
                    'path' => $file[0],
                    'type' => $identifier[0]);
            }
        }

        // Save for later reuse.
        $this->save();
    }

    /**
     * @param $file
     * @return bool|string
     */
    protected function findIdentifier($file)
    {

        $rsc = fopen($file, 'r');
        $stuctureName = $namespace = $code = $type = '';

        for ($k = 0; $k < 5; $k++) {

            if (feof($rsc)) {

                break;
            }

            $code .= fread($rsc, 2048);

            $tokens = @token_get_all($code);
            $count = count($tokens);

            for ($i = 2; $i < $count; $i++) {
                if ($tokens[$i][0] == T_NAMESPACE) {

                    for ($j = $i; $j < $count; $j++) {

                        if ($tokens[$j][0] === T_STRING) {

                            $namespace .= '\\' . $tokens[$j][1];

                        } elseif ($tokens[$j] === '{' || $tokens[$j] === ';') {

                            break;
                        }
                    }

                    continue;

                } elseif ($tokens[$i - 2][0] === T_CLASS
                    && $tokens[$i - 1][0] === T_WHITESPACE
                    && $tokens[$i][0] === T_STRING
                ) {

                    $type = 'class';
                    $stuctureName = $tokens[$i][1];
                    break 2;

                } elseif ($tokens[$i - 2][0] === T_TRAIT
                    && $tokens[$i - 1][0] === T_WHITESPACE
                    && $tokens[$i][0] === T_STRING
                ) {

                    $type = 'trait';
                    $stuctureName = $tokens[$i][1];
                    break 2;

                } elseif ($tokens[$i - 2][0] === T_INTERFACE
                    && $tokens[$i - 1][0] === T_WHITESPACE
                    && $tokens[$i][0] === T_STRING
                ) {

                    $type = 'interface';
                    $stuctureName = $tokens[$i][1];
                    break 2;

                }
            }
        }

        // Check what we got and return it accordingly. We will return an ugly array of the sort
        // array(<STRUCTURE_TYPE>, <STRUCTURE_NAME>).
        if (empty($stuctureName)) {

            return false;

        } elseif (empty($namespace)) {
            // We got no namespace, so just use the structure name

            return array($type, $stuctureName);

        } else {
            // We got both, so combine it.

            return array($type, ltrim($namespace . '\\' . $stuctureName, '\\'));
        }
    }

    /**
     * Will load a serialized map from the storage file, if it exists
     *
     * @return bool
     */
    protected function load()
    {
        if (is_readable($this->mapPath)) {

            $this->map = unserialize(file_get_contents($this->mapPath));
            return true;
        }

        return false;
    }

    /**
     * Will save this map into the storage file.
     *
     * @return bool
     */
    protected function save()
    {
        if (file_put_contents($this->mapPath, serialize($this->map)) >= 0) {

            return true;

        } else {

            return false;
        }
    }
}