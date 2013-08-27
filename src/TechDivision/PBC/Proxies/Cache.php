<?php

namespace TechDivision\PBC\Proxies;

use TechDivision\PBC\Interfaces\PBCCache;

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
     * @param $projectRoot
     */
    private function __construct()
    {

    }

    private function __clone()
    {

    }

    /**
     * @return mixed
     */
    public static function getInstance($projectRoot = '.')
    {
        if (self::$instance === null) {

            $self = new self;
            $self->files = $self->createFileMap($projectRoot . '/*');
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
        $map = array_merge($map, self::createFileMap(__DIR__ . self::GLOB_CACHE_PATTERN));
        // Filter for all self generated proxied classes
        $suffixOffset = strlen(PBC_PROXY_SUFFIX);
        foreach ($map as $class => $file) {

            if (strrpos($class, PBC_PROXY_SUFFIX) === strlen($class) - $suffixOffset) {

                unset($map[$class]);
            }
        }

        // When the map is ready we store it for later use
        file_put_contents(__DIR__ . '/cacheMap', serialize($map));

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
                $className = self::getClassIdentifier(realpath($items[$i]));
                if (empty($className) === false) {

                    $classMap[$className]['path'] = realpath($items[$i]);
                    $classMap[$className]['version'] = filemtime(realpath($items[$i]));
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
     * @param $className
     * @param $fileName
     * @return bool
     */
    public function add($className, $fileName)
    {
        // Add the entry
        $time = time();
        $this->map[$className] = array('version' => $time, 'path' => $fileName);
        $this->map['version'] = $time;

        // When the map is ready we store it for later use
        return (boolean)file_put_contents(__DIR__ . '/cacheMap', serialize($this->map));
    }

    /**
     * Will check if a certain file is cached in a current manner.
     *
     * @param $className
     * @return bool
     */
    public function isCached($className)
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