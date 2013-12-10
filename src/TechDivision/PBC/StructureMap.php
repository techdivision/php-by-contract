<?php

namespace TechDivision\PBC;

use TechDivision\PBC\Entities\Definitions\Structure;
use TechDivision\PBC\Exceptions\CacheException;
use TechDivision\PBC\Interfaces\MapInterface;

// We might run into a situation where we do not have proper autoloading in place here. So require our DTO.
require_once __DIR__ . DIRECTORY_SEPARATOR . 'Entities' . DIRECTORY_SEPARATOR .
    'Definitions' . DIRECTORY_SEPARATOR . 'Structure.php';

/**
 * Class StructureMap
 *
 * @package TechDivision\PBC
 */
class StructureMap implements MapInterface
{
    /**
     * @var array
     */
    protected $map = array();

    /**
     * @var array
     */
    protected $rootPathes;

    /**
     * @var string
     */
    protected $mapPath;

    /**
     * @var array
     */
    protected $omittedPathes;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param $rootPathes
     * @param Config $config
     * @param array $omittedPathes
     */
    public function __construct($rootPathes, Config $config = null, $omittedPathes = array())
    {
        // As we do accept arrays we have to be sure that we got one. If not we convert it.
        if (!is_array($rootPathes)) {

            $rootPathes = array($rootPathes);
        }

        // We might have further configuration within the directory configurations
        foreach ($rootPathes as $key => $rootPath) {

            if (is_array($rootPath) && isset($rootPath['dir'])) {

                $rootPathes[$key] = realpath($rootPath['dir']);
            }
        }

        // Save the config for later use.
        $this->config = $config;

        // Set the rootPath and calculate the path to the map file
        $this->rootPathes = $rootPathes;

        // Build up the path of the serialized map.
        $this->mapPath = PBC_MAP_DIR . DIRECTORY_SEPARATOR . md5(implode('', $rootPathes));

        // Set the omitted pathes
        $this->omittedPathes = $omittedPathes;

        // Load the serialized map.
        // If there is none or it isn't current we will generate it anew.
        if (!$this->load()) {

            $this->generate();
        }
    }

    /**
     * Will return all entries in our map.
     *
     * @param bool $contracted
     * @return Structure
     */
    public function getEntries($contracted = false)
    {
        // Our structures
        $structures = array();

        foreach ($this->map as $entry) {

            // If we only need contracted only
            if ($contracted === true && $entry['hasContracts'] === false) {

                continue;
            }

            $structures[] = new Structure($entry['cTime'],
                $entry['identifier'],
                $entry['path'],
                $entry['type'],
                $entry['hasContracts']);
        }

        // Return the structure DTOs
        return $structures;
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
        $this->map[$structure->getIdentifier()] = array(
            'cTime' => $structure->getCTime(),
            'identifier' => $structure->getIdentifier(),
            'path' => $structure->getPath(),
            'type' => $structure->getType(),
            'hasContracts' => $structure->hasContracts()
        );

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
                $entry['type'],
                $entry['hasContracts']);

            // Return the structure DTO
            return $structure;

        } else {

            return false;
        }
    }

    /**
     * Checks if the entry for a certain structure is current if one was specified.
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
     * Will break up any path into a canonical form like realpath(), but does not require the file to exist.
     *
     * @param $path
     * @return mixed
     */
    private function normalizePath($path)
    {
        return array_reduce(
            explode('/', $path),
            create_function(
                '$a, $b',
                '
                           if($a === 0)
                               $a = "/";

                           if($b === "")
                               return $a;

                           if($b === ".")
                               return __DIR__;

                           if($b === "..")
                               return dirname($a);

                           return preg_replace("/\/+/", "/", "$a/$b");
                       '
            ),
            0
        );
    }

    /**
     * Will generate the structure map within the specified root path.
     *
     * @return  bool
     */
    private function generate()
    {
        // As we might have several rootPathes we have to create several RecursiveDirectoryIterators.
        $directoryIterators = array();
        foreach ($this->rootPathes as $rootPath) {

            $directoryIterators[] = new \RecursiveDirectoryIterator($this->normalizePath($rootPath),
                \RecursiveDirectoryIterator::KEY_AS_PATHNAME);
        }

        // We got them all, now append them onto a new RecursiveIteratorIterator and return it.
        $recursiveIterator = new \AppendIterator();
        foreach ($directoryIterators as $directoryIterator) {

            $recursiveIterator->append(
                new \RecursiveIteratorIterator($directoryIterator,
                    \RecursiveIteratorIterator::SELF_FIRST,
                    \RecursiveIteratorIterator::CATCH_GET_CHILD)
            );
        }

        // Lets prepare the patter based on the existence of omitted pathes.
        if (!empty($this->omittedPathes)) {

            $pattern = '/[^' . str_replace('/', '\/', implode($this->omittedPathes, '|')) . ']\.php$/i';

        } else {

            $pattern = '/^.+\.php$/i';
        }

        $regexIterator = new \RegexIterator($recursiveIterator, $pattern, \RecursiveRegexIterator::GET_MATCH);

        foreach ($regexIterator as $file) {

            // Get the identifiers if any.
            $identifier = $this->findIdentifier($file[0]);

            if ($identifier !== false) {

                $this->map[$identifier[1]] = array(
                    'cTime' => filectime($file[0]),
                    'identifier' => $identifier[1],
                    'path' => $file[0],
                    'type' => $identifier[0],
                    'hasContracts' => $this->findContracts($file[0])
                );
            }
        }

        // Save for later reuse.
        $this->save();
    }

    /**
     * @param $file
     * @return bool
     */
    protected function findContracts($file)
    {
        // We need to get our array of needles
        $needles = array(PBC_KEYWORD_INVARIANT, PBC_KEYWORD_POST, PBC_KEYWORD_PRE);

        // If we have to enforce things like @param or @returns, we have to be more sensitive
        $enforcementConfig = $this->config->getConfig('enforcement');
        if ($enforcementConfig['enforce-default-type-safety'] === true) {

            $needles[] = '@var';
            $needles[] = '@param';
            $needles[] = '@return';
        }

        // Open the file and search it piece by piece until we find something or the file ends.
        $rsc = fopen($file, 'r');
        $recent = '';
        while (!feof($rsc)) {

            // Get a current chunk
            $current = fread($rsc, 512);

            // We also check the last chunk as well, to avoid cutting the only needle we have in two.
            $haystack = $recent . $current;
            foreach ($needles as $needle) {

                // If we found something we can return true
                if (strpos($haystack, $needle) !== false) {

                    return true;
                }
            }

            // Set recent for the next iteration
            $recent = $current;
        }

        // Still here? So nothing was found.
        return false;
    }

    /**
     * @param $file
     * @return array|bool
     * @throws Exceptions\CacheException
     */
    protected function findIdentifier($file)
    {

        $rsc = fopen($file, 'r');

        if ($rsc === false) {

            throw new CacheException();
        }

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