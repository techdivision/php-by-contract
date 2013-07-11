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

use TechDivision\PBC\Interfaces\Assertion;
use TechDivision\PBC\Entities\Definitions\ClassDefinition;
use TechDivision\PBC\Entities\Definitions\FunctionDefinition;
use TechDivision\PBC\Entities\Lists\FunctionDefinitionList;
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
     * @param $projectRoot
     */
    public function __construct($projectRoot)
    {
        // Building up the cache map
        $this->cacheMap = $this->getCacheMap();

        // Create the complete file map
        $this->fileMap = $this->getClassMap($projectRoot . '/*');
    }

    /**
     * @return array|mixed
     */
    private function getCacheMap()
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
     * @return bool
     */
    public function createProxy($className)
    {
        // If we do not know the file we can forget it
        if (isset($this->fileMap[$className]['path']) === false) {

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

        // First of all lets create the new proxied parent class
        $tmp = $this->createProxyParent($this->fileMap[$className]['path'], $classDefinition);

        // Only continue if successful before
        if ($tmp === false) {

            return false;
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
     * @param $fileName
     * @param ClassDefinition $classDefinition
     * @return bool
     */
    private function createProxyParent($fileName, ClassDefinition $classDefinition)
    {
        // Get the class tokens
        $tokens = token_get_all(file_get_contents($fileName));

        // There are certain parts of a class definition we have to exchange to be able to use it as a proxy's parent
        // Check the tokens
        for ($i = 0; $i < count($tokens); $i++) {



            // If we got the keyword final, we have to erase this token, otherwise we would get problems with our
            // proxy inheritance
            if ($tokens[$i][0] === T_FINAL) {

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

        // We worked ourselves through the class, now we might add invariant and some magic.
        // We can add them to the end of the class.
        array_pop($tokens);

        // Something to buffer
        $fileContent = '';

        // Create the invariant
        $fileContent .= 'private function ' . PBC_CLASS_INVARIANT_NAME . '() {';
        $iterator = $classDefinition->invariantConditions->getIterator();
        for ($i = 0; $i < $iterator->count(); $i++) {

            $fileContent .= $this->createAroundAdviceCode($iterator->current(), PBC_CLASS_INVARIANT_NAME);

            // Move the iterator
            $iterator->next();
        }
        $fileContent .= '}';

        // Now we need our magic __call method to catch any call to the invariant.
        $fileContent .= '
        /**
         * Magic function to forward calls of the proxy to our invariant.
         *
         * @throws BadMethodCallException
         */
        public function __call($name, $arguments)
        {
            // If we got called from our proxy class we will forward the call to the invariant.
            if ($name === "' . PBC_CLASS_INVARIANT_NAME . '" && get_called_class() === "'. $classDefinition->name .'") {

                $this->' . PBC_CLASS_INVARIANT_NAME . '();

            } else {

                throw new \BadMethodCallException;
            }
        }
        ';

        // Now we need our magic __set method to catch anybody who wants to change the attributes.
        // If we would not do so a client could break the class without triggering the invariant.
        $fileContent .= '
        /**
         * Magic function to forward writing property access calls if within visibility boundaries.
         *
         * @throws InvalidArgumentException
         */
        public function __set($name, $value)
        {
            // Does this property even exist? If not, throw an exception
            if ($this->attributes->offsetExists($name)) {

                throw new \InvalidArgumentException;

            }

            // Check if the invariant holds
            $this->' . PBC_CLASS_INVARIANT_NAME . '();

            // Now check what kind of visibility we would have
            $attribute = $this->attributes->offsetGet($name);
            switch ($attribute->visibility) {

                case "protected" :

                    if (is_subclass_of(get_called_class(), "'. $classDefinition->name .'")) {

                        $this->$name = $value;

                    } else {

                        throw new \InvalidArgumentException;
                    }
                    break;

                case "public" :

                    $this->$name = $value;
                    break;

                default :

                    throw new \InvalidArgumentException;
                    break;
            }

            // Check if the invariant holds
            $this->' . PBC_CLASS_INVARIANT_NAME . '();
        }
        ';

        // Add to token list as string
        $tokens[] = $fileContent;

        // Finally add the closing bracket we popped before
        $tokens[] = '}';

        // Create the proxy file from the token array
        $tmpFileName = str_replace(DIRECTORY_SEPARATOR, '_', $fileName);
        $targetFileName = __DIR__ . '/cache/' . str_replace('.php', '', $tmpFileName) . PBC_PROXY_SUFFIX . '.php';

        return $this->createFileFromTokens($targetFileName, $tokens);
    }

    /**
     * @param $targetFileName
     * @param ClassDefinition $classDefinition
     * @param FunctionDefinitionList $functionDefinitionList
     * @return bool
     */
    private function createFileFromDefinitions($targetFileName, ClassDefinition $classDefinition, FunctionDefinitionList $functionDefinitionList)
    {
        // Simply create the file content by traversing over the definitions and build a string from the
        // multiple parts of them.
        $fileContent = '<?php ';

        // Lets begin with the namespace
        if (!empty($classDefinition->namespace)) {

            $fileContent .= 'namespace ' . $classDefinition->namespace . ';';
        }

        // Also don't forget to require the parent class
        $tmpFile = str_replace('.php', '', $targetFileName);
        $fileContent .= 'require "' . $tmpFile . PBC_PROXY_SUFFIX . '.php";';

        // Tell them to use our exception namespaces
        $fileContent .= 'use TechDivision\PBC\Exceptions\BrokenPreConditionException;
        use TechDivision\PBC\Exceptions\BrokenPostConditionException;';

        // Next build up the class header
        $fileContent .= $classDefinition->docBlock;
        $fileContent .= 'class ' . $classDefinition->name . ' extends ' . $classDefinition->name . PBC_PROXY_SUFFIX . ' {';

        // We should create attributes to save old and result
        $fileContent .=
            '/**
            * @var mixed
            */
            private ' . PBC_KEYWORD_OLD . ';';

        // Create all the methods
        $functionIterator = $functionDefinitionList->getIterator();
        for ($i = 0; $i < $functionIterator->count(); $i++) {

            $functionDefinition = $functionIterator->current();

            // Create the method header
            $fileContent .= $functionDefinition->access . ' function ' . $functionDefinition->name . '(';
            $fileContent .= implode(', ', $functionDefinition->parameters) . ') {';

            // First of all check if our invariant holds
            $fileContent .= '$this->' . PBC_CLASS_INVARIANT_NAME . '();';

            // Iterate over all preconditions
            $assertionIterator = $functionDefinition->preConditions->getIterator();
            for ($k = 0; $k < $assertionIterator->count(); $k++) {

                $fileContent .= $this->createAroundAdviceCode($assertionIterator->current(), $functionDefinition->name, 'BrokenPreConditionException');

                // Next assertion please
                $assertionIterator->next();
            }

            // Do we have to keep an instance of $this to compare with old later?
            if ($functionDefinition->usesOld === true) {

                $fileContent .= '$this->' . PBC_KEYWORD_OLD . ' = clone $this;';
            }

            // We do not need typing for the parameters anymore, so omit it
            foreach ($functionDefinition->parameters as $key => $parameter) {

                $functionDefinition->parameters[$key] = strstr($parameter, '$');
            }

            // Now call the parent method itself
            $fileContent .= PBC_KEYWORD_RESULT . ' = parent::' . $functionDefinition->name .
                '(' . implode(', ', $functionDefinition->parameters) . ');';

            // Iterate over all postconditions
            $assertionIterator = $functionDefinition->postConditions->getIterator();
            for ($k = 0; $k < $assertionIterator->count(); $k++) {

                $fileContent .= $this->createAroundAdviceCode($assertionIterator->current(), $functionDefinition->name, 'BrokenPostConditionException');

                // Next assertion please
                $assertionIterator->next();
            }

            // Last of all check if our invariant holds
            $fileContent .= '$this->' . PBC_CLASS_INVARIANT_NAME . '();';

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