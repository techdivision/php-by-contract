<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 19.06.13
 * Time: 15:26
 * To change this template use File | Settings | File Templates.
 */

namespace TechDivision\PBC;

use TechDivision\PBC\CacheMap;
use TechDivision\PBC\StructureMap;
use TechDivision\PBC\Exceptions\GeneratorException;
use TechDivision\PBC\Entities\Definitions\FileDefinition;
use TechDivision\PBC\Entities\Definitions\ClassDefinition;
use TechDivision\PBC\Entities\Definitions\InterfaceDefinition;
use TechDivision\PBC\Entities\Definitions\StructureDefinitionHierarchy;
use TechDivision\PBC\Entities\Lists\TypedListList;
use TechDivision\PBC\Interfaces\Assertion;
use TechDivision\PBC\Interfaces\StructureDefinitionInterface;
use TechDivision\PBC\Entities\Definitions\Structure;
use TechDivision\PBC\Parser\StructureParserFactory;
use TechDivision\PBC\Config;

/**
 * Class Generator
 */
class Generator
{
    /**
     * @var \TechDivision\PBC\CacheMap
     */
    protected $cacheMap;

    /**
     * @var \TechDivision\PBC\StructureMap
     */
    protected $structureMap;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var StructureDefinitionHierarchy
     */
    protected $structureDefinitionHierarchy;

    /**
     * @param StructureMap $structureMap
     * @param CacheMap     $cache
     */
    public function __construct(StructureMap $structureMap, CacheMap $cache)
    {
        $this->cache = $cache;

        $this->structureMap = $structureMap;

        $config = Config::getInstance();
        $this->config = $config->getConfig('enforcement');

        $this->structureDefinitionHierarchy = new StructureDefinitionHierarchy();
    }

    /**
     * @param $className
     *
     * @return bool
     */
    public function update($className)
    {
        return $this->create($className, true);
    }

    /**
     * @param Structure $mapEntry
     * @param bool      $update
     *
     * @return bool
     * @throws GeneratorException
     */
    public function create(Structure $mapEntry, $update = false)
    {
        // We know what we are searching for and we got a fine factory so lets get us a parser
        $structureParserFactory = new StructureParserFactory();
        $parser = $structureParserFactory->getInstance(
            $mapEntry->getType(),
            $mapEntry->getPath(),
            $this->structureMap,
            $this->structureDefinitionHierarchy
        );

        // Lets get the definition we are looking for
        $structureDefinition = $parser->getDefinition($mapEntry->getIdentifier(), true);

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
     * @param $className
     *
     * @return string
     */
    private function createFilePath($className)
    {
        // As a file can contain multiple classes we will substitute the filename with the class name
        $tmpFileName = ltrim(str_replace('\\', '_', $className), '_');
        $this->config = Config::getInstance();
        $cacheConfig = $this->config->getConfig('cache');

        return $cacheConfig['dir'] . DIRECTORY_SEPARATOR . $tmpFileName . '.php';
    }

    /**
     * @param                              $targetFileName
     * @param StructureDefinitionInterface $structureDefinition
     *
     * @return mixed
     * @throws \InvalidArgumentException
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
     * We will just copy the file here until the autoloader got refactored.
     * TODO remove when autoloader is able to recoginize and skip interfaces
     *
     * @param                     $targetFileName
     * @param FileDefinition      $fileDefinition
     * @param InterfaceDefinition $structureDefinition
     */
    private function createFileFromInterfaceDefinition(
        $targetFileName,
        InterfaceDefinition $structureDefinition
    ) {
        // Get the content of the file
        $content = file_get_contents($structureDefinition->path);

        // Make the one change we need, the original file path and modification timestamp
        $content = str_replace(
            '<?php',
            '<?php /* ' . PBC_ORIGINAL_PATH_HINT . $structureDefinition->path . '#' .
            filemtime(
                $structureDefinition->path
            ) . PBC_ORIGINAL_PATH_HINT . ' */',
            $content
        );

        return (boolean)file_put_contents($targetFileName, $content);
    }

    /**
     * @param                 $targetFileName
     * @param ClassDefinition $structureDefinition
     *
     * @return bool
     */
    private function createFileFromClassDefinition(
        $targetFileName,
        ClassDefinition $structureDefinition
    ) {
        // Before using the definition we have to finalize it
        // $structureDefinition->finalize();

        $res = fopen(
            $this->createFilePath($structureDefinition->getQualifiedName()),
            'w+'
        );

        // Append all configured filters
        $this->appendFilter($res, $structureDefinition);

        $tmp = fwrite(
            $res,
            file_get_contents($structureDefinition->path, time())
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
     * @param                              $res
     * @param StructureDefinitionInterface $structureDefinition
     *
     * @return bool
     */
    protected function appendFilter(
        & $res,
        StructureDefinitionInterface $structureDefinition
    ) {
        // Lets get the enforcement level
        $enforcementConfig = $this->config->getConfig('enforcement');
        $levelArray = array();
        if (isset($enforcementConfig['level'])) {

            $levelArray = array_reverse(str_split(decbin($enforcementConfig['level'])));
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
            $iterator = $structureDefinition->functionDefinitions->getIterator();
            foreach ($iterator as $functionDefinition) {

                if ($functionDefinition->getPreconditions()->count() !== 0) {

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
                    $structureDefinition->functionDefinitions
                );
            }
        }

        // What about the postcondition filter?
        if (isset($levelArray[1]) && $levelArray[1] == 1) {

            // Do we even got any postconditions?
            $filterNeeded = false;
            $iterator = $structureDefinition->functionDefinitions->getIterator();
            foreach ($iterator as $functionDefinition) {

                if ($functionDefinition->getPostconditions()->count() !== 0) {

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
                    $structureDefinition->functionDefinitions
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
     * @param $className
     *
     * @return mixed
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
