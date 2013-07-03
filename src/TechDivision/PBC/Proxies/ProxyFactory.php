<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 19.06.13
 * Time: 15:26
 * To change this template use File | Settings | File Templates.
 */

namespace TechDivision\PBC\Proxies;

require_once __DIR__ . "/../Parser/AnnotationParser.php";

use TechDivision\PBC\Entities\Assertion;
use TechDivision\PBC\Entities\ClassDefinition;
use TechDivision\PBC\Entities\FunctionDefinition;
use TechDivision\PBC\Entities\FunctionDefinitionList;
use TechDivision\PBC\Parser\AnnotationParser;

/**
 * Class ProxyFactory
 */
class ProxyFactory
{
    /**
     * @var array
     */
    private $cacheMap;

    /**
     * @var array
     */
    private $fileMap;

    /**
     *
     */
    const GLOB_CACHE_PATTERN = '/cache/*';

    /**
     *
     */
    public function __construct($projectRoot)
    {
        // Building up the cache map
        $this->cacheMap = $this->getCacheMap();

        // Create the complete file map
        $this->fileMap = $this->getClassMap($projectRoot . '/*');
    }

    /**
     *
     */
    private function getCacheMap()
    {
        // We might already have a serialized map
        $mapFile = file_get_contents(__DIR__ . '/cacheMap');

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
        $map = array_merge($map, $this->getClassMap(__DIR__ . self::GLOB_CACHE_PATTERN));
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
    private function getClassMap($pattern)
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
                }
            }
        }

        return $classMap;
    }

    /**
     * @param $className
     */
    public function createProxy($className)
    {
        // If we do not know the file we can forget it
        if (isset($this->fileMap[$className]['path']) === false) {

            return false;
        }

        // First of all lets create the new proxied parent class
        $tmp = $this->createProxyParent($this->fileMap[$className]['path']);

        // Only continue if successful before
        if ($tmp === false) {

            return false;
        }

        // Get a parser for our annotations
        $parser = new AnnotationParser();

        // Get the class tokens
        $tokens = token_get_all(file_get_contents($this->fileMap[$className]['path']));

        // Traverse over all tokens and build up the class body
        $functionDefinitionList = new FunctionDefinitionList();
        for ($i = 0; $i < count($tokens); $i++) {

            // If we got the keyword private, we have to set it to protected, otherwise we will not be able to
            // inherit everything correctly
            if (is_array($tokens[$i])) {
                switch ($tokens[$i][0]) {

                    case T_FUNCTION:

                        $functionDefinitionList->offsetSet(NULL, $parser->getFunctionDefinition($tokens, $tokens[$i + 2][1]));

                        break;

                    case T_CLASS:

                        $classDefinition = $parser->getClassDefinition($tokens, $tokens[$i + 2][1]);

                        break;

                    default:

                        break;
                }
            }
        }

        // Create the proxy file from the token array
        $tmpFileName = str_replace(DIRECTORY_SEPARATOR, '_', $this->fileMap[$className]['path']);
        $targetFileName = __DIR__ . '/cache/' . $tmpFileName;

        $tmp = $this->createFileFromDefinitions($targetFileName, $classDefinition, $functionDefinitionList);

        // Only continue if successful before
        if ($tmp === false) {

            return false;
        }

        // Add the proxy class to our cache map
        return $this->pushCacheMap($className, $this->fileMap[$className]['path']);
    }

    /**
     * @param $className
     * @param $fileName
     *
     * @return bool
     */
    private function pushCacheMap($className, $fileName)
    {
        // Add the entry
        $time = time();
        $this->cacheMap[$className] = array('version' => $time, 'path' => $fileName);
        $this->cacheMap['version'] = $time;

        // When the map is ready we store it for later use
        return file_put_contents(__DIR__ . '/cacheMap', serialize($this->cacheMap));
    }

    /**
     *
     */
    private function createProxyParent($fileName)
    {
        // Get the class tokens
        $tokens = token_get_all(file_get_contents($fileName));

        // There are certain parts of a class definition we have to exchange to be able to use it as a proxy's parent
        // Check the tokens
        for ($i = 0; $i < count($tokens); $i++) {

            // If we got the keyword private, we have to set it to protected, otherwise we will not be able to
            // inherit everything correctly
            if ($tokens[$i][0] === T_PRIVATE) {

                // Set to protected
                $tokens[$i][0] = T_PROTECTED;
                $tokens[$i][1] = 'protected';

                continue;

            } elseif ($tokens[$i][0] === T_FINAL) {
                // If we got the keyword final, we have to erase this token, otherwise we would get problems with our
                // proxy inheritance

                // Just unset this part of the array
                unset($tokens[$i]);

                continue;

            } elseif ($tokens[$i][0] === T_CLASS) {
                // If we got the class name we have to add the proxied suffix

                for ($j = $i + 1; $j < count($tokens); $j++) {

                    if ($tokens[$j] === '{') {

                        $tokens[$i + 2][1] .= PBC_PROXY_SUFFIX;
                        break;
                    }
                }

                continue;
            }
        }

        // Create the proxy file from the token array
        $tmpFileName = str_replace(DIRECTORY_SEPARATOR, '_', $fileName);
        $targetFileName = __DIR__ . '/cache/' . str_replace('.php', '', $tmpFileName) . PBC_PROXY_SUFFIX . '.php';

        return $this->createFileFromTokens($targetFileName, $tokens);
    }

    /**
     * @param string $targetFileName
     * @param ClassDefinition $classDefinition
     * @param FunctionDefinition $functionDefinition
     *
     * @return bool
     */
    private function createFileFromDefinitions($targetFileName, ClassDefinition $classDefinition, FunctionDefinitionList $functionDefinitionList)
    {
        // Simply create the file content by traversing over the definitions and build a string from the
        // multiple parts of them.
        $fileContent = '<?php ';

        // Lets begin with the namespace
        if (! empty($classDefinition->namespace)) {

            $fileContent .= 'namespace ' . $classDefinition->namespace . ';';
        }

        // Also don't forget to require the parent class
        $tmpFile = str_replace('.php', '', $targetFileName);
        $fileContent .= 'require "' . $tmpFile . PBC_PROXY_SUFFIX . '.php";';

        // Next build up the class header
        $fileContent .= $classDefinition->docBlock;
        $fileContent .= 'class ' . $classDefinition->name . ' extends ' . $classDefinition->name . PBC_PROXY_SUFFIX . ' {';

        // We should create attributes to save old and result
        $fileContent .=
            '/**
            * @var mixed
            */
            private ' . PBC_KEYWORD_OLD . ';';

        // Create the invariant
        $fileContent .= 'private function ' . PBC_CLASS_INVARIANT_NAME . '() {';
        $iterator = $classDefinition->invariantConditions->getIterator();
        for ($i = 0;$i < $iterator->count(); $i++) {

            $fileContent .= $this->createAroundAdviceCode($iterator->current());

            // Move the iterator
            $iterator->next();
        }
        $fileContent .= '}';

        // Create all the methods
        $functionIterator = $functionDefinitionList->getIterator();
        for ($i = 0;$i < $functionIterator->count(); $i++) {

                $functionDefinition = $functionIterator->current();

                // Create the method header
                $fileContent .= $functionDefinition->access . ' function ' . $functionDefinition->name . '(';
                $fileContent .= implode(', ', $functionDefinition->parameters) . ') {';

                // First of all check if our invariant holds
                $fileContent .= '$this->' . PBC_CLASS_INVARIANT_NAME . '();';

                // Iterate over all preconditions
                $assertionIterator = $functionDefinition->preConditions->getIterator();
                for ($k = 0;$k < $assertionIterator->count(); $k++) {

                    $fileContent .= $this->createAroundAdviceCode($assertionIterator->current());
                }

                // Do we have to keep an instance of $this to compare with old later?
                if ($functionDefinition->usesOld === true) {

                    $fileContent .= '$this->' . PBC_KEYWORD_OLD . ' = $this;';
                }

                // Now call the parent method itself
                $fileContent .= PBC_KEYWORD_RESULT . ' = parent::' . $functionDefinition->name .
                    '(' . implode(', ', $functionDefinition->parameters) . ');';

                // First of all check if our invariant holds
                $fileContent .= '$this->' . PBC_CLASS_INVARIANT_NAME . '();';

                // Iterate over all postconditions
                $assertionIterator = $functionDefinition->postConditions->getIterator();
                for ($k = 0;$k < $assertionIterator->count(); $k++) {

                    $fileContent .= $this->createAroundAdviceCode($assertionIterator->current());
                }

                // If we passed every check we can return the result
                $fileContent .= 'return ' . PBC_KEYWORD_RESULT . ';}';

            // Move the iterator
            $functionIterator->next();
        }

        // Make the final closing bracket
        $fileContent .= '}';

        // Return if we succeeded or not
        return (boolean)file_put_contents($targetFileName, $fileContent);
    }

    /**
     * @param Assertion $assertion
     * @param string $exceptionType
     *
     * @return string
     */
    private function createAroundAdviceCode(Assertion $assertion, $exceptionType = 'Exception')
    {
        $result = '';
        // The beginning is always the same
        $result .= 'if (';
        // We have different Assertion types, so handle them differently
        if (strpos($assertion->operator, 'is_') === false) {

            $result .= $assertion->firstOperand . ' ' . $assertion->operator . ' ' . $assertion->secondOperand;
        } else {

            // Do we have an is_a or a simple type check?
            if ($assertion->operator === 'is_a') {

                $result .= $message = $assertion->operator . '(' . $assertion->firstOperand . ', ' . $assertion->secondOperand . ')';
                $result .= '=== false';

            } elseif ($assertion->secondOperand === NULL) {

                $result .= $message = $assertion->operator . '(' . $assertion->firstOperand . ')';
                $result .= '=== false';
            }
        }

        $result .= '){';
        $result .= '    throw new ' . $exceptionType . '("Assertion '. $message .' failed.");';
        $result .= '}';

        return $result;
    }

    /**
     * @param $targetFileName
     * @param array $tokens
     *
     * @return bool
     */
    private function createFileFromTokens($targetFileName, array $tokens)
    {
        // Simply create the file content by traversing over the token array and build a string from the
        // multiple parts of the array.
        $fileContent = '';
        foreach ($tokens as $token) {

            if (is_string($token)) {

                $fileContent .= $token;

            } elseif (is_array($token) && isset($token[1])) {

                $fileContent .= $token[1];
            }
        }

        // Return if we succeeded or not
        return (boolean)file_put_contents($targetFileName, $fileContent);
    }

    /**
     * @param $className
     *
     * @return mixed
     */
    public function getProxyFileName($className)
    {
        if (!isset($this->cacheMap[$className]) || !isset($this->cacheMap[$className]['path'])) {

            return false;
        }

        $tmpFileName = str_replace(DIRECTORY_SEPARATOR, '_', $this->fileMap[$className]['path']);
        return realpath(__DIR__ . '/cache/' . $tmpFileName);
    }

    /**
     * Will check if a certain file is cached in a current manner.
     *
     * @param $className
     *
     * @return bool
     */
    public function isCached($className)
    {
        if (isset($this->cacheMap[$className]) &&
            $this->cacheMap[$className]['version'] >= filemtime($this->fileMap[$className]['path'])
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