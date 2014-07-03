<?php
/**
 * File containing the Exporter class
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

require_once __DIR__ . '/Bootstrap.php';

/**
 * TechDivision\PBC\Exporter
 *
 * This class provides tools to export already contracted structure definitions.
 * This is useful if one does not want to use the autoloading functionality or does want to include the lib at all
 *
 * @category  Php-by-contract
 * @package   TechDivision\PBC
 * @author    Bernhard Wick <b.wick@techdivision.com>
 * @copyright 2014 TechDivision GmbH - <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      http://www.techdivision.com/
 */
class Exporter
{
    /**
     * @var CacheMap
     */
    private $cache;

    /**
     * @var StructureMap
     */
    private $structureMap;

    /**
     * Will export all structures found in the specified source path and will export it to the target dir
     *
     * @param string $source The source directory
     * @param string $target The target directory
     *
     * @throws \InvalidArgumentException
     * @throws \Exception
     *
     * @return boolean
     */
    public function export($source, $target)
    {
        // Check if we got a valid source directory
        if (!is_readable($source)) {

            throw new \InvalidArgumentException('Source folder missing or not readable.');
        }

        // Check if we got a valid target directory
        if (!is_dir($target) || !is_writeable($target)) {

            throw new \InvalidArgumentException('Target folder missing or not writable.');
        }

        // We are still here so everything seems to be according to plan
        // We should clean our fileMap
        $this->cache = new CacheMap('PBC_CACHE_DIR');

        $config = Config::getInstance();
        $this->config = $config->getConfig();
        $this->structureMap = new StructureMap($config['autoloader']['dirs'], $config['enforcement']['dirs']);

        // Get all files within this dir
        $tmpFiles = $this->cache->getFiles();
        $fileList = array();
        $sourcePath = realpath($source);
        foreach ($tmpFiles as $class => $tmpFile) {

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
        foreach ($parsedFiles as $origPath => $parsedPath) {

            if (isset($tmpFileList[$origPath])) {

                unset($fileList[$tmpFileList[$origPath]]);
            }
        }

        // Next step!
        $parsedFiles = array_merge($parsedFiles, $this->getFreshlyParsedFiles($fileList));

        // Create all the files!!
        foreach ($parsedFiles as $origPath => $parsedPath) {

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
     * Will check which of the targeted files are already cached
     *
     * @param array $fileList List of files to export
     *
     * @return array
     */
    private function getCachedFiles(array $fileList)
    {
        // Get all the cached classes
        $classCache = $this->cache->get();

        // Get all the parsed classes of our requested files
        $parsedFiles = array();
        foreach ($fileList as $class => $file) {

            if (isset($classCache[$class])) {

                $parsedFiles[$file] = $classCache[$class]['path'];
            }
        }

        return $parsedFiles;
    }

    /**
     * Will generate the altered structure definitions
     *
     * @param array $fileList List of files to export
     *
     * @return array
     */
    private function getFreshlyParsedFiles(array $fileList)
    {
        // Get a code generator and use it!
        $generator = new Generator($this->structureMap, $this->cache);

        foreach ($fileList as $class => $file) {

            $generator->create($class);
        }

        // Now everything should be cached ;)
        return $this->getCachedFiles($fileList);
    }
}
