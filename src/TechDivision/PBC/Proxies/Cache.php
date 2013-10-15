<?php

namespace TechDivision\PBC\Proxies;

use TechDivision\PBC\Interfaces\PBCCache;
use TechDivision\PBC\Entities\Definitions\ClassDefinition;
use TechDivision\PBC\Parser\ClassParser;

class Cache implements PBCCache
{
    /**
     * @var array
     */
    private $map;

    /**
     * @var array
     */
    private $files;

    /**
     * @var Cache
     */
    private static $instance = null;

    /**
     *
     */
    const GLOB_CACHE_PATTERN = '/cache/*';

    /**
     *
     */
    private function __construct()
    {

    }

    /**
     *
     */
    private function __clone()
    {

    }

    /**
     * @param string $projectRoot
     * @return Cache
     */
    public static function getInstance($projectRoot = '.')
    {
        if (self::$instance === null) {

            $self = new self;
            $self->files = $self->createFiles($projectRoot . '/*');
            $self->map = $self->createMap();
            self::$instance = $self;
        }

        return self::$instance;
    }

    /**
     *
     */
    public function createMap()
    {
        // We might already have a serialized map
        $mapFile = false;
        if (is_readable(__DIR__ . '/cacheMap') === true) {

            $mapFile = file_get_contents(__DIR__ . '/cacheMap');
        }

        if (is_string($mapFile)) {
            // We got the file unserialize it
            $map = unserialize($mapFile);

            // Lets check if it is current, if yes, return what we got
            if (isset($map['version']) && $map['version'] == filemtime(__DIR__ . '/cache')) {

                return $map;
            }
        }

        // We have none (or old one), create it.
        // Get the timestamp of the cache folder first so we would not miss a file if it got written during
        // a further check
        $map = array('version' => filemtime(__DIR__ . '/cache'));
        $map = array_merge($map, $this->createFileMap(__DIR__ . self::GLOB_CACHE_PATTERN));

        // When the map is ready we store it for later use
        file_put_contents(__DIR__ . '/cacheMap', serialize($map));

        // Return what we produced
        return $map;
    }

    /**
     * @param $pattern
     * @return array|mixed
     */
    private function createFiles($pattern)
    {
        // We might already have a serialized map
        $mapFile = false;
        if (is_readable(__DIR__ . '/fileMap') === true) {

            $mapFile = file_get_contents(__DIR__ . '/fileMap');
        }

        if (is_string($mapFile)) {
            // We got the file unserialize it
            $map = unserialize($mapFile);

            // Lets check if it is current and references the same projectRoot, if yes, return what we got
            if (isset($map['version']) && $map['version'] == filemtime(dirname($pattern)) &&
                    isset($map['projectRoot']) && $map['projectRoot'] === dirname($pattern)) {

                return $map;
            }
        }

        // We have none (or old one), create it.
        // Get the timestamp of the cache folder first so we would not miss a file if it got written during
        // a further check
        $map = array('version' => filemtime(dirname($pattern)), 'projectRoot' => dirname($pattern));
        $map = array_merge($map, $this->createFileMap($pattern));

        // When the map is ready we store it for later use
        file_put_contents(__DIR__ . '/fileMap', serialize($map));

        // Return what we produced
        return $map;
    }

    /**
     * @param $pattern
     *
     * @return array
     */
    private function createFileMap($pattern)
    {
        $classMap = array();
        $items = glob($pattern);

        for ($i = 0; $i < count($items); $i++) {
            if (is_dir($items[$i])) {

                $add = glob($items[$i] . '/*');
                $items = array_merge($items, $add);

            } else {

                // This is not a dir, so check if it contains a class
                $className = $this->getClassIdentifier(realpath($items[$i]));
                if (empty($className) === false) {

                    $classMap[$className]['path'] = realpath($items[$i]);
                    $classMap[$className]['version'] = filemtime(realpath($items[$i]));

                    // We also have to check if there are any dependencies
                    $parser = new ClassParser();

                    $classDefinition = $parser->getDefinitionFromFile(realpath($items[$i]), $className);

                    if ($classDefinition instanceof ClassDefinition) {

                        $classMap[$className]['dependencies'] = $classDefinition->implements;
                        $classMap[$className]['dependencies'][] = $classDefinition->extends;
                    }
                }
            }
        }

        return $classMap;
    }

    /**
     * @return array
     */
    public function get()
    {
        return $this->map;
    }

    /**
     * @return array
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * @param $classIdentifier
     * @param ClassDefinition $classDefinition
     * @param $fileName
     * @return bool
     */
    public function add($classIdentifier, ClassDefinition $classDefinition, $fileName)
    {
        // Add the entry
        $time = time();

        // Lets get all classes which depend on this one
        $dependencies = $classDefinition->implements;
        $dependencies[] = $classDefinition->extends;

        $this->map[$classIdentifier] = array('version' => $time, 'path' => $fileName, 'dependencies' => $dependencies);
        $this->map['version'] = $time;

        // When the map is ready we store it for later use
        return (boolean)file_put_contents(__DIR__ . '/cacheMap', serialize($this->map));
    }

    /**
     * @param $classIdentifier
     * @return array
     */
    public function getDependants($classIdentifier)
    {
        $dependants = array();
        foreach ($this->map as $className => $class) {

            if (isset($class['dependencies']) && is_array($class['dependencies'])) {

                foreach ($class['dependencies'] as $dependency) {

                    // If this cache entry is depending on the class $classIdentifier we have to say so
                    if ($dependency === $classIdentifier) {

                        $dependants[] = $className;
                    }
                }
            }
        }

        return $dependants;
    }

    /**
     * Will check if a certain file is cached in a current manner.
     *
     * @param $className
     * @return bool
     */
    public function isCached($className)
    {
        if (isset($this->map[$className])) {

            return true;

        } else {

            return false;
        }
    }

    /**
     * @param $className
     * @return bool
     */
    public function isCurrent($className)
    {
        if (isset($this->map[$className]) &&
            $this->map[$className]['version'] >= filemtime($this->files[$className]['path'])
        ) {

            return true;

        } else {

            return false;
        }
    }

    /**
     * @param $classIdentifier
     * @return bool
     */
    public function touch($classIdentifier)
    {
        if(isset($this->map[$classIdentifier])) {

            $this->map[$classIdentifier]['version'] = time();
            return true;

        } else {

            return false;
        }
    }

    /**
     * @param $fileName
     *
     * @return string
     */
    private function getClassIdentifier($fileName)
    {
        // Lets open the file readonly
        $fileResource = fopen($fileName, 'r');

        // Prepare some variables we will need
        $className = '';
        $namespace = '';
        $buffer = '';

        // Declaring the iterator here, to not check the start of the file again and again
        $i = 0;
        while (empty($className) === true) {

            // Is the file over already?
            if (feof($fileResource)) {

                break;
            }

            // We only read a small portion of the file, as we should find the class declaration up front
            $buffer .= fread($fileResource, 512);
            // Get all the tokens in the read buffer
            $tokens = @token_get_all($buffer);

            // If we did not reach anything of value yet we will continue reading
            if (strpos($buffer, '{') === false) {

                continue;
            }

            // Check the tokens
            for (; $i < count($tokens); $i++) {

                // If we got the class name
                if ($tokens[$i][0] === T_CLASS) {

                    for ($j = $i + 1; $j < count($tokens); $j++) {

                        if ($tokens[$j] === '{') {

                            $className = $tokens[$i + 2][1];
                        }
                    }
                }

                // If we got the namespace
                if ($tokens[$i][0] === T_NAMESPACE) {

                    for ($j = $i + 1; $j < count($tokens); $j++) {

                        if ($tokens[$j][0] === T_STRING) {

                            $namespace .= $tokens[$j][1] . '\\';

                        } elseif ($tokens[$j] === '{' || $tokens[$j] === ';') {

                            break;
                        }
                    }
                }
            }
        }

        // Return what we did or did not found
        return $namespace . $className;
    }
}