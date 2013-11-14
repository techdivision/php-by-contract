<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 19.06.13
 * Time: 15:26
 * To change this template use File | Settings | File Templates.
 */

namespace TechDivision\PBC\Proxies;

use TechDivision\PBC\CacheMap;
use TechDivision\PBC\Entities\Definitions\FileDefinition;
use TechDivision\PBC\Entities\Definitions\ClassDefinition;
use TechDivision\PBC\Entities\Definitions\InterfaceDefinition;
use TechDivision\PBC\Entities\Lists\TypedListList;
use TechDivision\PBC\Interfaces\Assertion;
use TechDivision\PBC\Interfaces\StructureDefinition;
use TechDivision\PBC\Entities\Definitions\Structure;
use TechDivision\PBC\Parser\FileParser;
use TechDivision\PBC\Config;
use TechDivision\PBC\StreamFilters\SkeletonFilter;
use TechDivision\PBC\StructureMap;

/**
 * Class ProxyFactory
 */
class ProxyFactory
{

    /**
     * @var \TechDivision\PBC\CacheMap
     */
    private $cache;

    /**
     * @var \TechDivision\PBC\StructureMap
     */
    private $structureMap;

    /**
     * @var array
     */
    private $config;

    /**
     * @param $structureMap
     * @param $cache
     */
    public function __construct(StructureMap $structureMap, CacheMap $cache)
    {
        $this->structureMap = $structureMap;
        $this->cache = $cache;

        $config = new Config();
        $this->config = $config->getConfig('Enforcement');
    }

    /**
     * @param $className
     * @return bool
     */
    public function updateProxy($className)
    {
        return $this->createProxy($className, true);
    }

    /**
     * @param $className
     * @param bool $update
     * @return bool
     */
    public function createProxy($className, $update = false)
    {
        // If we do not know the file we can forget it
        $mapEntry = $this->structureMap->getEntry($className);
        if (!$mapEntry instanceof Structure) {

            return false;
        }

        // We know the class and we know the file it is in, so get our FileParser and have a blast
        $fileParser = new FileParser();
        $fileDefinition = $fileParser->getDefinitionFromFile($mapEntry->getPath());

        // So we got our FileDefinition, now lets check if there are multiple classes in there.
        // Iterate over all classes within the FileDefinition and create a file for each of them
        $classIterator = $fileDefinition->structureDefinitions->getIterator();
        for ($k = 0; $k < $classIterator->count(); $k++) {


            $structureDefinition = $classIterator->current();
            $filePath = $this->createProxyFilePath($fileDefinition->namespace . '\\' . $structureDefinition->name);

            $tmp = $this->createFileFromDefinitions($filePath, $fileDefinition, $structureDefinition);

            if ($tmp === true) {

                // Now get our new file into the cacheMap
                $this->cache->add(new Structure(filectime($mapEntry->getPath()), $className, $filePath, 'class'));

                if ($update === true) {
                    // If this was an update we might have to update possible children as well, as contracts are inherited
                    $dependants = $this->cache->getDependants($className);

                    foreach ($dependants as $dependant) {

                        $this->updateProxy($dependant, true);
                    }
                }
            }

            // Next assertion please
            $classIterator->next();
        }

        // Still here? Than everything worked out great.
        return true;
    }

    /**
     * @param $className
     * @return string
     */
    private function createProxyFilePath($className)
    {
        // As a file can contain multiple classes we will substitute the filename with the class name
        $tmpFileName = ltrim(str_replace('\\', '_', $className), '_');
        return __DIR__ . '/cache/' . $tmpFileName . '.php';
    }

    /**
     * @param $targetFileName
     * @param FileDefinition $fileDefinition
     * @param StructureDefinition $structureDefinition
     * @return mixed
     * @throws \InvalidArgumentException
     */
    private function createFileFromDefinitions($targetFileName, FileDefinition $fileDefinition, StructureDefinition $structureDefinition)
    {
        // We have to check which structure type we got
        $definitionType = get_class($structureDefinition);

        // Call the method accordingly
        $tmp = explode('\\', $definitionType);
        $creationMethod = 'createFileFrom' . array_pop($tmp) . 's';

        // Check if we got something
        if (!method_exists($this, $creationMethod)) {

            throw new \InvalidArgumentException();
        }

        return $this->$creationMethod($targetFileName, $fileDefinition, $structureDefinition);
    }

    /**
     * We will just copy the file here until the autoloader got refactored.
     * TODO remove when autoloader is able to recoginize and skip interfaces
     *
     * @param $targetFileName
     * @param FileDefinition $fileDefinition
     * @param InterfaceDefinition $structureDefinition
     */
    private function createFileFromInterfaceDefinitions($targetFileName, FileDefinition $fileDefinition, InterfaceDefinition $structureDefinition)
    {
        return (boolean) file_put_contents($targetFileName, file_get_contents($fileDefinition->path . DIRECTORY_SEPARATOR . $fileDefinition->name));
    }

    /**
     * @param $targetFileName
     * @param FileDefinition $fileDefinition
     * @param ClassDefinition $structureDefinition
     * @return bool
     * @throws \Exception|PHPParser_Error
     */
    private function createFileFromClassDefinitions($targetFileName, FileDefinition $fileDefinition, ClassDefinition $structureDefinition)
    {
        // Before using the definition we have to finalize it
        $structureDefinition->finalize();

        stream_filter_register('SkeletonFilter', 'TechDivision\PBC\StreamFilters\SkeletonFilter');
        stream_filter_register('PreconditionFilter', 'TechDivision\PBC\StreamFilters\PreconditionFilter');
        stream_filter_register('PostconditionFilter', 'TechDivision\PBC\StreamFilters\PostconditionFilter');
        stream_filter_register('InvariantFilter', 'TechDivision\PBC\StreamFilters\InvariantFilter');
        stream_filter_register('ProcessingFilter', 'TechDivision\PBC\StreamFilters\ProcessingFilter');
        stream_filter_register('BeautifyFilter', 'TechDivision\PBC\StreamFilters\BeautifyFilter');

        $res = fopen($this->createProxyFilePath($structureDefinition->namespace . '\\' . $structureDefinition->name), 'w+');

        stream_filter_append($res, 'SkeletonFilter', STREAM_FILTER_WRITE, $structureDefinition->functionDefinitions);
        stream_filter_append($res, 'PreconditionFilter', STREAM_FILTER_WRITE, $structureDefinition->functionDefinitions);
        stream_filter_append($res, 'PostconditionFilter', STREAM_FILTER_WRITE, $structureDefinition->functionDefinitions);
        stream_filter_append($res, 'InvariantFilter', STREAM_FILTER_WRITE, $structureDefinition);
        stream_filter_append($res, 'ProcessingFilter', STREAM_FILTER_WRITE, $this->config);
        //stream_filter_append($res, 'BeautifyFilter', STREAM_FILTER_WRITE, $this->config);

        $tmp = fwrite($res, file_get_contents($fileDefinition->path . DIRECTORY_SEPARATOR . $fileDefinition->name));

        // Did we write something?
        if ($tmp > 0) {

            return true;

        } else {

            // Delete the empty file stub we made
            unlink($this->createProxyFilePath($structureDefinition->namespace .
                '\\' . $structureDefinition->name), $res);
            return false;
        }
    }

    /**
     * @param $for
     * @param $message
     * @return string
     */
    private function generateReactionCode($for, $message)
    {
        $code = '';

        // What kind of reaction should we create?
        switch ($this->config['processing']) {

            case 'exception':

                // What kind of exception do we need?
                switch ($for) {

                    case 'precondition':

                        $exception = 'BrokenPreconditionException';
                        break;

                    case 'postcondition':

                        $exception = 'BrokenPostconditionException';
                        break;

                    case 'invariant':

                        $exception = 'BrokenInvariantException';
                        break;

                    default:

                        $exception = '\Exception';
                        break;
                }
                // Create the code
                $code .= '$this->' . PBC_CONTRACT_DEPTH . '--;
                throw new ' . $exception . '(\'' . $message . '\');';

                break;

            case 'logging':

                // Create the code
                $code .= '$logger = new \\' . $this->config['logger'] . '();
                $logger->error(\'Broken ' . $for . ' with message: ' . $message . ' in \' . __METHOD__);';
                break;

            default:

                break;
        }

        return $code;
    }

    /**
     * @param TypedListList $conditionLists
     * @param $methodName
     * @param $type
     * @return string
     */
    private function generateAroundAdviceCode(TypedListList $conditionLists, $methodName, $type)
    {
        // What kind of types do we handle?
        $allowedTypes = array_flip(array('precondition', 'postcondition', 'invariant'));

        if (!isset($allowedTypes[$type])) {

            return '';
        }

        // We only use contracting if we're not inside another contract already
        $code = 'if ($this->' . PBC_CONTRACT_DEPTH . ' < 2) {';

        // Preconditions need or-ed conditions so we make sure only one conditionlist gets checked
        if ($type === 'precondition') {

            $code .= '$passedOne = false;
                $failedAssertion = array();';

        }

        // We need a counter to check how much conditions we got
        $conditionCounter = 0;
        $listIterator = $conditionLists->getIterator();
        for ($i = 0; $i < $listIterator->count(); $i++) {

            // Create the inner loop for the different assertions
            $assertionIterator = $listIterator->current()->getIterator();

            // Only act if we got actual entries
            if ($assertionIterator->count() === 0) {

                // increment the outer loop
                $listIterator->next();
                continue;
            }

            $codeFragment = array();
            for ($j = 0; $j < $assertionIterator->count(); $j++) {

                $codeFragment[] = $assertionIterator->current()->getString();

                $assertionIterator->next();
            }

            // Preconditions need or-ed conditions so we make sure only one conditionlist gets checked
            $conditionCounter++;
            if ($type === 'precondition') {

                $code .= 'if ($passedOne === false && !((';
                $code .= implode(') && (', $codeFragment) . '))){';
                $code .= '$failedAssertion[] = \'(' . str_replace('\'', '"', implode(') && (', $codeFragment)) . ')\';';
                $code .= '} else {$passedOne = true;}';

            } else {

                $code .= 'if (!((';
                $code .= implode(') && (', $codeFragment) . '))){';
                $code .= $this->generateReactionCode($type, 'Assertion (' . str_replace('\'', '"', implode(') && (', $codeFragment)) .
                    ') failed');
                $code .= '}';
            }

            // increment the outer loop
            $listIterator->next();
        }

        // Preconditions need or-ed conditions so we make sure only one conditionlist gets checked
        if ($type === 'precondition' && $conditionCounter > 0) {

            $code .= 'if ($passedOne === false){';
            $code .= $this->generateReactionCode($type, 'Assertions \' . implode(", ", $failedAssertion) . \' failed');
            $code .= '}';
        }

        // Closing bracket for contract depth check
        $code .= '}';

        return $code;
    }

    /**
     * @param StructureDefinition $structureDefinition
     * @return string
     */
    private function createInvariantCode(StructureDefinition $structureDefinition)
    {
        // We might have ancestral, interface and direct invariants, so collect them first so we can better handle them later
        $invariants = $structureDefinition->ancestralInvariants;
        $invariants->add($structureDefinition->invariantConditions);

        $code = '';

        $invariantIterator = $invariants->getIterator();
        for ($i = 0; $i < $invariantIterator->count(); $i++) {

            // Create the inner loop for the different assertions
            if ($invariantIterator->current()->count() !== 0) {
                $assertionIterator = $invariantIterator->current()->getIterator();
                $codeFragment = array();
                for ($j = 0; $j < $assertionIterator->count(); $j++) {

                    $codeFragment[] = $assertionIterator->current()->getString();

                    $assertionIterator->next();
                }
                $code .= 'if (!(';
                $code .= implode(' && ', $codeFragment) . ')){';
                $code .= 'throw new BrokenInvariantException(\'Assertion ' . str_replace('\'', '"', implode(' && ', $codeFragment)) .
                    ' failed in ' . PBC_CLASS_INVARIANT_NAME . '.\');';
                $code .= '}';
            }
            // increment the outer loop
            $invariantIterator->next();
        }

        return $code;
    }

    /**
     * @param $invariantUsed
     * @param $position
     * @return string
     */
    private function createInvariantCall($invariantUsed, $position)
    {
        $allowed_positions = array_flip(array('entry', 'exit'));

        if ($invariantUsed !== true || !isset($allowed_positions[$position])) {

            return '';
        }

        // Decide how our if statement should look depending on the position of the invariant
        if ($position === 'entry') {

            $code = 'if ($this->' . PBC_CONTRACT_DEPTH . ' === 0) {
            $this->' . PBC_CLASS_INVARIANT_NAME . '();}
            // Tell them we entered a contracted method
            $this->' . PBC_CONTRACT_DEPTH . '++;';

        } elseif ($position === 'exit') {

            $code = 'if ($this->' . PBC_CONTRACT_DEPTH . ' === 1) {
            $this->' . PBC_CLASS_INVARIANT_NAME . '();}
            // Tell them we are done at this level
            $this->' . PBC_CONTRACT_DEPTH . '--;';
        }

        return $code;
    }

    /**
     * @param Assertion $assertion
     * @param $functionName
     * @param string $exceptionType
     * @return string
     */
    private function createAroundAdviceCode(Assertion $assertion, $functionName, $exceptionType = 'Exception')
    {
        $result = '';

        // The beginning is always the same
        $result .= 'if (' . $assertion->getInvertString();
        $result .= '){';
        $result .= '    throw new ' . $exceptionType . '(\'Assertion ' . str_replace('\'', '"', $assertion->getString()) .
            ' failed in ' . $functionName . '.\');';
        $result .= '}';

        return $result;
    }

    /**
     * @param $className
     *
     * @return mixed
     */
    public function getProxyFileName($className)
    {
        $mapEntry = $this->cache->getEntry($className);

        if (!$mapEntry instanceof Structure) {

            return false;
        }

        return $mapEntry->getPath();
    }
}