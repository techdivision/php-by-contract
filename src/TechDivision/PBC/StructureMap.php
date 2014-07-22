<?php
/**
 * File containing the StructureMap class
 *
 * PHP version 5
 *
 * @category  Php-by-contract
 * @package   TechDivision\PBC
 * @author    Bernhard Wick <b.wick@techdivision.com>
 * @copyright 2014 TechDivision GmbH - <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      http://www.techdivision.com/
 */

namespace TechDivision\PBC;

use TechDivision\PBC\Entities\Definitions\Structure;
use TechDivision\PBC\Exceptions\CacheException;
use TechDivision\PBC\Interfaces\MapInterface;
use TechDivision\PBC\Utils\Formatting;

// Load the constants if not already done
require_once __DIR__ . DIRECTORY_SEPARATOR . 'Constants.php';

// We might run into a situation where we do not have proper autoloading in place here. So require our DTO.
require_once __DIR__ . DIRECTORY_SEPARATOR . 'Entities' . DIRECTORY_SEPARATOR .
    'Definitions' . DIRECTORY_SEPARATOR . 'Structure.php';

/**
 * TechDivision\PBC\StructureMap
 *
 * This class provides the possibility to hold a map of structure entries, which are used to relate a structure
 * definition to it's physical path and other meta information
 *
 * @category  Php-by-contract
 * @package   TechDivision\PBC
 * @author    Bernhard Wick <b.wick@techdivision.com>
 * @copyright 2014 TechDivision GmbH - <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      http://www.techdivision.com/
 */
class StructureMap implements MapInterface
{
    /**
     * @var array $map The actual container for the map
     */
    protected $map;

    /**
     * @var array $rootPaths The $autoloaderPaths and $enforcementPaths combined to build up paths we have to index
     */
    protected $rootPaths;

    /**
     * @var array $enforcementPaths Which paths do we like to include in our map?
     */
    protected $autoloaderPaths;

    /**
     * @var array $enforcementPaths Which paths do we have to enforce
     */
    protected $enforcementPaths;

    /**
     * @var string $mapPath Where will the map be stored?
     */
    protected $mapPath;

    /**
     * @var \TechDivision\PBC\Config $config Configuration
     */
    protected $config;

    /**
     * @var \Iterator|null $projectIterator Will hold the iterator over the project root pathes if needed
     */
    protected $projectIterator;

    /**
     * @var string $version Will hold the version of the currently loaded map
     */
    protected $version;

    /**
     * Default constructor
     *
     * @param array                    $autoloaderPaths  Which paths do we like to include in our map?
     * @param array                    $enforcementPaths Which paths do we have to enforce
     * @param \TechDivision\PBC\Config $config           Configuration
     */
    public function __construct($autoloaderPaths, $enforcementPaths, Config $config)
    {
        // Init as empty map
        $this->map = array();

        // As we do accept arrays we have to be sure that we got one. If not we convert it.
        if (!is_array($enforcementPaths)) {

            $enforcementPaths = array($enforcementPaths);
        }
        if (!is_array($autoloaderPaths)) {

            $autoloaderPaths = array($autoloaderPaths);
        }

        // Save the config for later use.
        $this->config = $config;

        // Set the enforcementPaths and autoloaderPaths and calculate the path to the map file
        $this->enforcementPaths = $enforcementPaths;
        $this->autoloaderPaths = $autoloaderPaths;

        // The rootPaths member holds the other path members combined to build up the root paths we have to create
        // an index for
        $this->rootPaths = array_merge($autoloaderPaths, $enforcementPaths);

        // Build up the path of the serialized map.
        $cacheConfig = $this->config->getConfig('cache');
        $this->mapPath = $cacheConfig['dir'] . DIRECTORY_SEPARATOR . md5(
            implode('', $autoloaderPaths) . implode('', $enforcementPaths)
        );
    }

    /**
     * Will return a list of files which are within the enforcementPaths
     *
     * @return array
     */
    protected function getEnforcedFiles()
    {
        // get an iterator over all files we have to enforce so we can compare the files
        $recursiveEnforcementIterator = $this->getDirectoryIterator($this->enforcementPaths);
        $regexEnforcementIterator = new \RegexIterator(
            $recursiveEnforcementIterator,
            '/^.+\.php$/i',
            \RecursiveRegexIterator::GET_MATCH
        );

        // iterator over our enforcement iterators and build up an array for later checks
        $enforcedFiles = array();
        foreach ($regexEnforcementIterator as $file) {

            // collect what we got in a way we can search it fast
            $enforcedFiles[$file[0]] = '';
        }

        return $enforcedFiles;
    }

    /**
     * Will return all entries within a map. If needed only entries of contracted
     * structures will be returned.
     *
     * @param boolean $contracted Do we only want entries containing contracts?
     * @param boolean $enforced   Do we only want entries which are enforced?
     *
     * @return mixed
     */
    public function getEntries($contracted = false, $enforced = false)
    {
        // Our structures
        $structures = array();

        foreach ($this->map as $entry) {

            // If we only need contracted only
            if (($contracted === true && $entry['hasContracts'] === false)) {

                continue;
            }

            // If we only need enforced only
            if (($enforced === true && $entry['enforced'] === false)) {

                continue;
            }

            $structures[] = new Structure(
                $entry['cTime'],
                $entry['identifier'],
                $entry['path'],
                $entry['type'],
                $entry['hasContracts'],
                $entry['enforced']
            );
        }

        // Return the structure DTOs
        return $structures;
    }

    /**
     * Will add a structure entry to the map.
     *
     * @param \TechDivision\PBC\Entities\Definitions\Structure $structure The structure to add
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
            'type' => $structure->getType(),
            'hasContracts' => $structure->hasContracts(),
            'enforced' => $structure->isEnforced()
        );

        // Persist the map
        return $this->save();
    }

    /**
     * Will return true if there is no map generated by now. False if there are map entries
     *
     * @return boolean
     */
    public function isEmpty()
    {
        return empty($this->map);
    }

    /**
     * Do we have an entry for the given identifier
     *
     * @param string $identifier The identifier of the entry we try to find
     *
     * @return bool
     */
    public function entryExists($identifier)
    {
        return isset($this->map[$identifier]);
    }

    /**
     * Will fill the structure map according to it's config
     *
     * @return boolean
     */
    public function fill()
    {
        // Load the serialized map.
        // If there is none or it isn't current we will generate it anew.
        if (!$this->load()) {

            $this->generate();
        }

        // Still here? Sounds good
        return true;
    }

    /**
     * Will update a given structure.
     * If the entry does not exist we will create it
     *
     * @param \TechDivision\PBC\Entities\Definitions\Structure|null $structure The structure to update
     *
     * @return void
     *
     * TODO implement this in the implementing classes
     */
    public function update(Structure $structure = null)
    {

    }

    /**
     * Will return the entry specified by it's identifier.
     * If none is found, false will be returned.
     *
     * @param string $identifier The identifier of the entry we try to find
     *
     * @return boolean|\TechDivision\PBC\Entities\Definitions\Structure
     */
    public function getEntry($identifier)
    {
        if (is_string($identifier) && isset($this->map[$identifier])) {

            // We got it, lets build a structure object
            $entry = $this->map[$identifier];
            $structure = new Structure(
                $entry['cTime'],
                $entry['identifier'],
                $entry['path'],
                $entry['type'],
                $entry['hasContracts'],
                $entry['enforced']
            );

            // Return the structure DTO
            return $structure;

        } else {

            return false;
        }
    }

    /**
     * Checks if the entry for a certain structure is recent if one was specified.
     * If not it will check if the whole map is recent.
     *
     * @param null|string $identifier The identifier of the entry we try to find
     *
     * @return  boolean
     */
    public function isRecent($identifier = null)
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

            // Is the saved version the same as of the current file system?
            if ($this->version === $this->findVersion()) {

                return true;
            }
        }

        // Still here? That seems wrong.
        return $result;
    }

    /**
     * Will return an array of all entry identifiers which are stored in this map.
     * We might filter by entry type
     *
     * @param string|null $type The type to filter by
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
     * @param boolean $fullPath Do we need the full path?
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
     * @param null|string $identifier The identifier of the entry we try to find
     *
     * @return boolean
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
     * Will reindex the structure map aka create it anew.
     * If $specificPath is supplied we will reindex the specified path only and add it to the map.
     * $specificPath MUST be within the root pathes of this StructureMap instance, otherwise it is no REindexing.
     *
     * @param string|null $specificPath A certain path which will be reindexed
     *
     * @return boolean
     */
    public function reIndex($specificPath = null)
    {
        // If we have no specific path we will delete the current map
        if ($specificPath === null) {

            // Make our map empty
            $this->map = array();

        } else {

            // We need some formatting utilities and normalize the path as it might contain regex
            $formattingUtil = new Formatting();
            $specificPath = $formattingUtil->normalizePath($specificPath);

            // If there was a specific path give, we have to check it for compatibility with $this.
            // First thing: is it contained in one of $this root pathes, if not it is no REindexing
            $isContained = false;
            foreach ($this->rootPaths as $rootPath) {

                if (strpos($specificPath, $rootPath) === 0) {

                    $isContained = true;
                    break;
                }
            }

            // Did we find it?
            if (!$isContained) {

                return false;
            }

            // Second thing: is the path readable?
            if (!is_readable($specificPath)) {

                return false;
            }

            // Everything fine, set the root path to our specific path
            $this->rootPaths = array($specificPath);
        }

        // Generate the map, all needed details have been altered above
        $this->generate();
        return true;
    }

    /**
     * Will return a "version" of the current project file base by checking the cTime of all directories
     * within the project root paths and create a sha1 hash over them.
     *
     * @return string
     */
    protected function findVersion()
    {
        $recursiveIteratore = $this->getProjectIterator();

        $tmp = '';
        foreach ($recursiveIteratore as $fileInfo) {

            if ($fileInfo->isDir()) {

                $tmp += $fileInfo->getCTime();
            }
        }

        return sha1($tmp);
    }

    /**
     * Will Return an iterator over our project files
     *
     * @return \Iterator
     */
    protected function getProjectIterator()
    {
        // If we already got it we can return it directly
        if (isset($this->projectIterator)) {

            return $this->projectIterator;
        }

        // Save our result for later reuse
        $this->projectIterator = $this->getDirectoryIterator($this->rootPaths);

        return $this->projectIterator;
    }

    /**
     * Will Return an iterator over a set of files determined by a list of directories to iterate over
     *
     * @param array $paths List of directories to iterate over
     *
     * @return \Iterator
     */
    protected function getDirectoryIterator(array $paths)
    {

        // As we might have several rootPaths we have to create several RecursiveDirectoryIterators.
        $directoryIterators = array();
        foreach ($paths as $path) {

            $directoryIterators[] = new \RecursiveDirectoryIterator(
                $path,
                \RecursiveDirectoryIterator::SKIP_DOTS
            );
        }

        // We got them all, now append them onto a new RecursiveIteratorIterator and return it.
        $recursiveIterator = new \AppendIterator();
        foreach ($directoryIterators as $directoryIterator) {

            // Append the directory iterator
            $recursiveIterator->append(
                new \RecursiveIteratorIterator(
                    $directoryIterator,
                    \RecursiveIteratorIterator::SELF_FIRST,
                    \RecursiveIteratorIterator::CATCH_GET_CHILD
                )
            );
        }

        return $recursiveIterator;
    }


    /**
     * Will generate the structure map within the specified root path.
     *
     * @return void
     */
    protected function generate()
    {
        // first of all we will get the version, so we will later know about changes made DURING indexing
        $this->version = $this->findVersion();

        // get the iterator over our project files and create a regex iterator to filter what we got
        $recursiveIterator = $this->getProjectIterator();
        $regexIterator = new \RegexIterator($recursiveIterator, '/^.+\.php$/i', \RecursiveRegexIterator::GET_MATCH);

        // get the list of enforced files
        $enforcedFiles= $this->getEnforcedFiles();

        // if we got namespaces which are omitted from enforcement we have to mark them as such
        $omittedNamespaces = array();
        if ($this->config->hasValue('enforcement/omit')) {

            $omittedNamespaces = $this->config->getValue('enforcement/omit');
        }

        // iterator over our project files and add array based structure representations
        foreach ($regexIterator as $file) {

            // get the identifiers if any.
            $identifier = $this->findIdentifier($file[0]);

            // if we got an identifier we can build up a new map entry
            if ($identifier !== false) {

                // check if the file has contracts and if it should be enforced
                $hasContracts = $this->findContracts($file[0]);

                // create the entry
                $this->map[$identifier[1]] = array(
                    'cTime' => filectime($file[0]),
                    'identifier' => $identifier[1],
                    'path' => $file[0],
                    'type' => $identifier[0],
                    'hasContracts' => $hasContracts,
                    'enforced' => $this->isFileEnforced(
                        $file[0],
                        $identifier[1],
                        $hasContracts,
                        $enforcedFiles,
                        $omittedNamespaces
                    )
                );
            }
        }

        // save for later reuse.
        $this->save();
    }

    /**
     * Check if the file should be enforced by considering three factors:
     *  1. does it have contracts?
     *  2. is it within the list of enforced files?
     *  3. is it within the namespaces omitted of enforcement?
     *
     * @param string  $file              The path of the file to be tested
     * @param string  $fileIdentifier    The qualified name of the file's structure
     * @param boolean $hasContracts      Does this file contain contracts (as epr current configuration)
     * @param array   $enforcedFiles     Array of files which need to be enforced
     * @param array   $omittedNamespaces Array of namespaces which are omitted from the enforcement
     *
     * @return boolean
     */
    protected function isFileEnforced($file, $fileIdentifier, $hasContracts, $enforcedFiles, $omittedNamespaces)
    {
        // if the file is within an omitted namespace it most certainly is not
        foreach ($omittedNamespaces as $omittedNamespace) {

            if (strpos($fileIdentifier, ltrim($omittedNamespace, '\\')) === 0) {

                return false;
            }
        }

        // as we are still here we are not within an omitted namespace.
        // if both of the below is true the file needs to be enforced
        if ($hasContracts === true && isset($enforcedFiles[$file])) {

            return true;

        } else {

            return false;
        }
    }

    /**
     * Will return true if the specified file has specified contracts, false if not.
     *
     * @param string $file File to check for contracts
     *
     * @return bool
     */
    protected function findContracts($file)
    {
        // We need to get our array of needles
        $needles = array(PBC_KEYWORD_INVARIANT, PBC_KEYWORD_POST, PBC_KEYWORD_PRE);

        // If we have to enforce things like @param or @returns, we have to be more sensitive
        if ($this->config->getValue('enforcement/enforce-default-type-safety') === true) {

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
     * Will get the identifier of a structure within a name.
     * Identifier will be most likely the qualified name including namespace and structure name.
     * May return false on error.
     *
     * @param string $file The file to check
     *
     * @return array|boolean
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
        // Can we read the intended path?
        if (is_readable($this->mapPath)) {

            // Get the map
            $this->map = unserialize(file_get_contents($this->mapPath));

            // Get the version and remove it from the map
            $this->version = $this->map['version'];
            unset($this->map['version']);

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
        // Add the version to the map
        $this->map['version'] = $this->version;

        // try to serialize into the known path
        if (file_put_contents($this->mapPath, serialize($this->map)) >= 0) {

            // Remove the version entry and return the result
            unset($this->map['version']);

            return true;

        } else {

            // Remove the version entry and return the result
            unset($this->map['version']);

            return false;
        }
    }
}
