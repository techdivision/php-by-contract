<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 05.09.13
 * Time: 11:57
 * To change this template use File | Settings | File Templates.
 */

namespace TechDivision\PBC;

use TechDivision\PBC\Proxies\Cache;
use TechDivision\PBC\Proxies\ProxyFactory;

require_once __DIR__ . '/Bootstrap.php';

/**
 * Class Exporter
 * @package TechDivision\PBC
 */
class Exporter
{
    /**
     * @var Cache
     */
    private $cache;

    /**
     * @param $source
     * @param $target
     * @return bool
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public function export($source, $target)
    {
        // Check if we got a valid source directory
        if (!is_readable($source)) {

            throw new \InvalidArgumentException('Source folder missing or not readable.');
        }

        // Check if we got a valid target directory
        if (!is_dir($target) || !is_writeable($target)) {

            throw new \InvalidArgumentException('Target folder missing or not writeable.');
        }

        // We are still here so everything seems to be according to plan
        // We should clean our fileMap
        $this->cache = Cache::getInstance(dirname($source));

        // Get all files within this dir
        $tmpFiles = $this->cache->getFiles();
        $fileList = array();
        $sourcePath = realpath($source);
        foreach($tmpFiles as $class => $tmpFile) {

            if (isset($tmpFile['path']) && strpos($tmpFile['path'], $sourcePath) === 0) {

                $fileList[$class] = str_replace($sourcePath, '', $tmpFile['path']);
            }
        }

        // Remember how mcuh we got for later checks
        $sourceCount = count($fileList);

        // No we got all the files which we can handle. So do something nice with them.
        // First of all get all the files we might have inside our cache.
        $parsedFiles = $this->getCachedFiles($fileList);

        // Return all the already found files from our $fileList and give it another try with parsing them anew.
        $tmpFileList = array_flip($fileList);
        foreach($parsedFiles as $origPath => $parsedPath) {

           if (isset($tmpFileList[$origPath])) {

               unset($fileList[$tmpFileList[$origPath]]);
           }
        }

        // Next step!
        $parsedFiles = array_merge($parsedFiles, $this->getFreshlyParsedFiles($fileList));

        // Create all the files!!
        foreach($parsedFiles as $origPath => $parsedPath) {

            // Have a look where to write the new file
            $targetPath = str_replace('\\\\', '\\', $target . DIRECTORY_SEPARATOR . $origPath);
            // Make the dirs if needed
            if (!is_writeable(dirname($targetPath))) {

                mkdir(dirname($targetPath), 0755, true);
            }
            // Write content to file
            file_put_contents($targetPath, file_get_contents($parsedPath));
        }

        // Did we get all of them?
        if ($sourceCount !== count($parsedFiles)) {

            throw new \Exception('Could not parse all needed files. Some will be missing.');
        }

        return true;
    }

    /**
     * @param array $fileList
     * @return array
     */
    private function getCachedFiles(array $fileList)
    {
        // Get all the cached classes
        $classCache = $this->cache->get();

        // Get all the parsed classes of our requested files
        $parsedFiles = array();
        foreach($fileList as $class => $file) {

            if (isset($classCache[$class])) {

                $parsedFiles[$file] = $classCache[$class]['path'];
            }
        }

        return $parsedFiles;
    }

    /**
     * @param array $fileList
     * @return array
     */
    private function getFreshlyParsedFiles(array $fileList)
    {
        // Get a proxy factory and use it!
        $proxyFactory = new ProxyFactory($this->cache);

        foreach($fileList as $class => $file) {

            $proxyFactory->createProxy($class);
        }

        // Now everything should be cached ;)
        return $this->getCachedFiles($fileList);
    }
}