<?php
/**
 * File containing the Generator class
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

use TechDivision\PBC\CacheMap;
use TechDivision\PBC\StructureMap;
use TechDivision\PBC\Exceptions\GeneratorException;
use TechDivision\PBC\Entities\Definitions\ClassDefinition;
use TechDivision\PBC\Entities\Definitions\InterfaceDefinition;
use TechDivision\PBC\Entities\Definitions\StructureDefinitionHierarchy;
use TechDivision\PBC\Interfaces\StructureDefinitionInterface;
use TechDivision\PBC\Entities\Definitions\Structure;
use TechDivision\PBC\Parser\StructureParserFactory;
use TechDivision\PBC\Config;

/**
 * TechDivision\PBC\Generator
 *
 * This class initiates the creation of enforced structure definitions.
 *
 * @category  Php-by-contract
 * @package   TechDivision\PBC
 * @author    Bernhard Wick <b.wick@techdivision.com>
 * @copyright 2014 TechDivision GmbH - <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      http://www.techdivision.com/
 *
 * TODO we are dealing with structures here, not just classes. Change variables and comments accordingly
 */
class Generator
{
    /**
     * @var \TechDivision\PBC\CacheMap $cacheMap A cacheMap instance to organize our cache
     */
    protected $cacheMap;

    /**
     * @var \TechDivision\PBC\StructureMap $structureMap A structureMap instance to organize the known structures
     */
    protected $structureMap;

    /**
     * @var array $config The aspect of the configuration we need
     */
    protected $config;

    /**
     * @var StructureDefinitionHierarchy
     */
    protected $structureDefinitionHierarchy;

    /**
     * Default constructor
     *
     * @param \TechDivision\PBC\StructureMap $structureMap A structureMap instance to organize the known structures
     * @param \TechDivision\PBC\CacheMap     $cache        A cacheMap instance to organize our cache
     * @param \TechDivision\PBC\Config       $config       Configuration
     */
    public function __construct(StructureMap $structureMap, CacheMap $cache, Config $config)
    {
        $this->cache = $cache;

        $this->structureMap = $structureMap;

        $this->config = $config;

        $this->structureDefinitionHierarchy = new StructureDefinitionHierarchy();
    }

    /**
     * Method used to update certain structures
     *
     * @param string $className Name of the structure we want to update
     *
     * @return boolean
     */
    public function update($className)
    {
        return $this->create($className, true);
    }

    /**
     * Will create an altered definition of the structure defined in the $mapEntry variable.
     * Will also add it to the cache map
     *
     * @param \TechDivision\PBC\Entities\Definitions\Structure $mapEntry        Entry of a StructureMap we want created
     * @param boolean                                          $createRecursive If contract inheritance is enabled
     *
     * @throws \TechDivision\PBC\Exceptions\GeneratorException
     *
     * @return boolean
     */
    public function create(Structure $mapEntry, $createRecursive = false)
    {
        // We know what we are searching for and we got a fine factory so lets get us a parser
        $structureParserFactory = new StructureParserFactory();
        $parser = $structureParserFactory->getInstance(
            $mapEntry->getType(),
            $mapEntry->getPath(),
            $this->config,
            $this->structureMap,
            $this->structureDefinitionHierarchy
        );

        // Lets get the definition we are looking for
        $structureDefinition = $parser->getDefinition($mapEntry->getIdentifier(), $createRecursive);

        if (!$structureDefinition instanceof StructureDefinitionInterface) {

            return false;
        }

        $qualifiedName = $structureDefinition->getQualifiedName();
        $filePath = $this->createFilePath(
            $qualifiedName,
            $mapEntry->getPath()
        );

        $tmp = $this->createFileFromDefinition($filePath, $structureDefinition);

        if ($tmp === false) {

            throw new GeneratorException('Could not create contracted definition for ' . $qualifiedName);
        }
        // Now get our new file into the cacheMap
        $this->cache->add(
            new Structure(
                filectime($mapEntry->getPath()),
                $qualifiedName,
                $filePath,
                $structureDefinition->getType()
            )
        );

        // Still here? Than everything worked out great.
        return true;
    }

    /**
     * Will return the path the cached and altered definition will have
     *
     * @param string $className Name of the structure we want to update
     *
     * @return string
     *
     * TODO implement this somewhere more accessible, others might need it too (e.g. autoloader)
     */
    private function createFilePath($className)
    {
        // As a file can contain multiple classes we will substitute the filename with the class name
        $tmpFileName = ltrim(str_replace('\\', '_', $className), '_');

        return $this->config->getValue('cache/dir') . DIRECTORY_SEPARATOR . $tmpFileName . '.php';
    }

    /**
     * Will create a file containing the altered definition
     *
     * @param string                                                    $targetFileName      The intended name of the
     *                                                                                       new file
     * @param \TechDivision\PBC\Interfaces\StructureDefinitionInterface $structureDefinition The definition of the
     *                                                                                       structure we will alter
     *
     * @throws \InvalidArgumentException
     *
     * @return boolean
     */
    private function createFileFromDefinition(
        $targetFileName,
        StructureDefinitionInterface $structureDefinition
    ) {
        // We have to check which structure type we got
        $definitionType = get_class($structureDefinition);

        // Call the method accordingly
        $tmp = explode('\\', $definitionType);
        $creationMethod = 'createFileFrom' . array_pop($tmp);

        // Check if we got something
        if (!method_exists($this, $creationMethod)) {

            throw new \InvalidArgumentException();
        }

        return $this->$creationMethod($targetFileName, $structureDefinition);
    }

    /**
     * Will create a file for a given interface definition.
     * We will just copy the file here until the autoloader got refactored.
     *
     * @param string                                                     $targetFileName      The intended name of the
     *                                                                                        new file
     * @param \TechDivision\PBC\Entities\Definitions\InterfaceDefinition $structureDefinition The definition of the
     *                                                                                        structure we will alter
     *
     * @return boolean
     *
     * TODO remove when autoloader is able to recognize and skip interfaces
     */
    private function createFileFromInterfaceDefinition(
        $targetFileName,
        InterfaceDefinition $structureDefinition
    ) {
        // Get the content of the file
        $content = file_get_contents($structureDefinition->getPath());

        // Make the one change we need, the original file path and modification timestamp
        $content = str_replace(
            '<?php',
            '<?php /* ' . PBC_ORIGINAL_PATH_HINT . $structureDefinition->getPath() . '#' .
            filemtime(
                $structureDefinition->getPath()
            ) . PBC_ORIGINAL_PATH_HINT . ' */',
            $content
        );

        return (boolean)file_put_contents($targetFileName, $content);
    }

    /**
     * Will create a file with the altered class definition as it's content
     *
     * @param string                                                 $targetFileName      The intended name of the
     *                                                                                    new file
     * @param \TechDivision\PBC\Entities\Definitions\ClassDefinition $structureDefinition The definition of the
     *                                                                                    structure we will alter
     *
     * @return bool
     */
    private function createFileFromClassDefinition(
        $targetFileName,
        ClassDefinition $structureDefinition
    ) {

        $res = fopen(
            $this->createFilePath($structureDefinition->getQualifiedName()),
            'w+'
        );

        // Append all configured filters
        $this->appendFilter($res, $structureDefinition);

        $tmp = fwrite(
            $res,
            file_get_contents($structureDefinition->getPath(), time())
        );

        // Did we write something?
        if ($tmp > 0) {

            fclose($res);

            return true;

        } else {

            // Delete the empty file stub we made
            unlink(
                $this->createFilePath(
                    $structureDefinition->getQualifiedName()
                ),
                $res
            );

            fclose($res);

            return false;
        }
    }

    /**
     * Will append all needed filters based on the enforcement level stated in the configuration file.
     *
     * @param resource                                                  $res                 The resource we will append
     *                                                                                       the filters to
     * @param \TechDivision\PBC\Interfaces\StructureDefinitionInterface $structureDefinition Structure definition
     *                                                                                       providing needed
     *                                                                                       information
     *
     * @return boolean
     *
     * TODO more comments!
     */
    protected function appendFilter(
        & $res,
        StructureDefinitionInterface $structureDefinition
    ) {
        // Lets get the enforcement level
        $levelArray = array();
        if ($this->config->hasValue('enforcement/level')) {

            $levelArray = array_reverse(str_split(decbin($this->config->getValue('enforcement/level'))));
        }

        // Whatever the enforcement level is, we will always need the skeleton filter.
        stream_filter_register('SkeletonFilter', 'TechDivision\PBC\StreamFilters\SkeletonFilter');
        stream_filter_append(
            $res,
            'SkeletonFilter',
            STREAM_FILTER_WRITE,
            $structureDefinition
        );

        // Now lets register and append the filers if they are mapped to a 1
        // Lets have a look at the precondition filter first
        if (isset($levelArray[0]) && $levelArray[0] == 1) {

            // Do we even got any preconditions?
            $filterNeeded = false;
            $iterator = $structureDefinition->getFunctionDefinitions()->getIterator();
            foreach ($iterator as $functionDefinition) {

                if ($functionDefinition->getAllPreconditions()->count() !== 0) {

                    $filterNeeded = true;
                    break;
                }
            }

            if ($filterNeeded) {

                stream_filter_register('PreconditionFilter', 'TechDivision\PBC\StreamFilters\PreconditionFilter');
                stream_filter_append(
                    $res,
                    'PreconditionFilter',
                    STREAM_FILTER_WRITE,
                    $structureDefinition->getFunctionDefinitions()
                );
            }
        }

        // What about the postcondition filter?
        if (isset($levelArray[1]) && $levelArray[1] == 1) {

            // Do we even got any postconditions?
            $filterNeeded = false;
            $iterator = $structureDefinition->getFunctionDefinitions()->getIterator();
            foreach ($iterator as $functionDefinition) {

                if ($functionDefinition->getAllPostconditions()->count() !== 0) {

                    $filterNeeded = true;
                    break;
                }
            }

            if ($filterNeeded) {

                stream_filter_register('PostconditionFilter', 'TechDivision\PBC\StreamFilters\PostconditionFilter');
                stream_filter_append(
                    $res,
                    'PostconditionFilter',
                    STREAM_FILTER_WRITE,
                    $structureDefinition->getFunctionDefinitions()
                );
            }
        }

        // What about the invariant filter?
        if (isset($levelArray[2]) && $levelArray[2] == 1) {

            // Do we even got any invariants?
            if ($structureDefinition->getInvariants()->count(true) !== 0) {

                stream_filter_register('InvariantFilter', 'TechDivision\PBC\StreamFilters\InvariantFilter');
                stream_filter_append($res, 'InvariantFilter', STREAM_FILTER_WRITE, $structureDefinition);
            }
        }

        // We ALWAYS need the processing filter. Everything else would not make any sense
        stream_filter_register('ProcessingFilter', 'TechDivision\PBC\StreamFilters\ProcessingFilter');
        stream_filter_append($res, 'ProcessingFilter', STREAM_FILTER_WRITE, $this->config);

        // We arrived here without any thrown exceptions, return true
        return true;
    }

    /**
     * Return the cache path (as organized by our cache map) for a given structure name
     *
     * @param string $className The structure we want the cache path for
     *
     * @return boolean|string
     */
    public function getFileName($className)
    {
        $mapEntry = $this->cache->getEntry($className);

        if (!$mapEntry instanceof Structure) {

            return false;
        }

        return $mapEntry->getPath();
    }
}
