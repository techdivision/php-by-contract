<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 19.06.13
 * Time: 15:26
 * To change this template use File | Settings | File Templates.
 */

namespace TechDivision\PBC\Proxies;

use TechDivision\PBC\Entities\Definitions\FileDefinition;
use TechDivision\PBC\Interfaces\Assertion;
use TechDivision\PBC\Entities\Definitions\ClassDefinition;
use TechDivision\PBC\Entities\Lists\FunctionDefinitionList;
use TechDivision\PBC\Parser\FileParser;

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

        // We know the class and we know the file it is in, so get our FileParser and have a blast
        $fileParser = new FileParser();
        $fileDefinition = $fileParser->getDefinitionFromFile($this->fileMap[$className]['path']);

        // So we got our FileDefinition, now lets check if there are multiple classes in there.
        // Iterate over all classes within the FileDefinition and create a file for each of them
        $classIterator = $fileDefinition->classDefinitions->getIterator();
        for ($k = 0; $k < $classIterator->count(); $k++) {

            $classDefinition = $classIterator->current();
            $filePath = $this->createProxyFilePath($this->fileMap[$className]['path'], $classDefinition->name);

            $tmp = $this->createFileFromDefinitions($filePath, $fileDefinition, $classDefinition);

            if ($tmp === true) {

                // Now get our new file into the cacheMap
                $this->pushCacheMap($className, $filePath);
            }

            // Next assertion please
            $classIterator->next();
        }

        // Still here? Than everything worked out great.
        return true;
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
     * @param $className
     * @return string
     */
    private function createProxyFilePath($fileName, $className)
    {
        // As a file can contain multiple classes we will substitute the filename with the class name
        $tmpFileName = dirname($fileName);
        $tmpFileName .= '_' . $className;
        $tmpFileName = str_replace(DIRECTORY_SEPARATOR, '_', $tmpFileName);
        return __DIR__ . '/cache/' . $tmpFileName . '.php';
    }

    /**
     * @param $targetFileName
     * @param FileDefinition $fileDefinition
     * @param ClassDefinition $classDefinition
     * @return bool
     */
    private function createFileFromDefinitions($targetFileName, FileDefinition $fileDefinition, ClassDefinition $classDefinition)
    {
        // This variable is used to determine if we need an invariant, as we might as well not.
        $invariantUsed = false;
        if ($classDefinition->invariantConditions->isEmpty() === false) {

            $invariantUsed = true;
        }

        // Simply create the file content by traversing over the definitions and build a string from the
        // multiple parts of them.
        $fileContent = '<?php ';

        // Lets begin with the namespace
        if (!empty($fileDefinition->namespace)) {

            $fileContent .= 'namespace ' . $fileDefinition->namespace . ';';
        }

        // Tell them to use our exception namespaces
        $fileContent .= 'use TechDivision\PBC\Exceptions\BrokenPreConditionException;
        use TechDivision\PBC\Exceptions\BrokenPostConditionException;
        use TechDivision\PBC\Exceptions\BrokenInvariantException;
        ';

        // Also include the use statements that where already present in the source file
        foreach ($fileDefinition->usedNamespaces as $usedNamespace) {

            $fileContent .= 'use ' . $usedNamespace . ';
            ';
        }

        // Next build up the class header
        $fileContent .= $classDefinition->docBlock;

        // Now check if we need any keywords for the class identity
        if ($classDefinition->isFinal) {

            $fileContent .= 'final ';
        }
        if ($classDefinition->isAbstract) {

            $fileContent .= 'abstract ';
        }

        $fileContent .= 'class ' . $classDefinition->name;

        // Add any parent class or interfaces there might be.
        if ($classDefinition->extends !== '') {

            $fileContent .= ' extends ' . $classDefinition->extends;
        }

        if (!empty($classDefinition->implements)) {

            $fileContent .= ' implements ' . implode(', ', $classDefinition->implements);
        }

        $fileContent .= ' {';

        // We should create attributes to save old instance state
        $fileContent .=
            '/**
            * @var mixed
            */
            private ' . PBC_KEYWORD_OLD . ';
            ';

        // We should create attributes to store our attribute types
        $fileContent .=
            '/**
            * @var array
            */
            private $attributes = array(';

        // After iterate over the attributes and build up our array
        $iterator = $classDefinition->attributeDefinitions->getIterator();
        for ($i = 0; $i < $iterator->count(); $i++) {

            // Get the current attribute for more easy access
            $attribute = $iterator->current();

            $fileContent .= '"' . substr($attribute->name, 1) . '"';
            $fileContent .= ' => array("visibility" => "' . $attribute->visibility . '", ';

            // Now check if we need any keywords for the variable identity
            if ($attribute->isStatic) {

                $fileContent .= '"static" => true';

            } else {

                $fileContent .= '"static" => false';
            }
            $fileContent .= '),';

            // Move the iterator
            $iterator->next();
        }
        $fileContent .= ');
        ';

        // After that we should enter all the other attributes
        $iterator = $classDefinition->attributeDefinitions->getIterator();
        for ($i = 0; $i < $iterator->count(); $i++) {

            // Get the current attribute for more easy access
            $attribute = $iterator->current();

            $fileContent .= 'private ';

            // Now check if we need any keywords for the variable identity
            if ($attribute->isStatic) {

                $fileContent .= 'static ';
            }

            $fileContent .= $attribute->name;

            // Do we have a default value
            if ($attribute->defaultValue !== null) {

                $fileContent .= ' = ' . $attribute->defaultValue;
            }

            $fileContent .= ';';

            // Move the iterator
            $iterator->next();
        }

        // Create the invariant
        if ($invariantUsed === true) {
            $fileContent .= 'private function ' . PBC_CLASS_INVARIANT_NAME . '() {';
            $iterator = $classDefinition->invariantConditions->getIterator();
            for ($i = 0; $i < $iterator->count(); $i++) {

                $fileContent .= $this->createAroundAdviceCode($iterator->current(), PBC_CLASS_INVARIANT_NAME, 'BrokenInvariantException');

                // Move the iterator
                $iterator->next();
            }
            $fileContent .= '}
        ';
        }

        // Now we need our magic __set method to catch anybody who wants to change the attributes.
        // If we would not do so a client could break the class without triggering the invariant.
        $fileContent .= '/**
         * Magic function to forward writing property access calls if within visibility boundaries.
         *
         * @throws InvalidArgumentException
         */
        public function __set($name, $value)
        {
            // Does this property even exist? If not, throw an exception
            if (isset($this->attributes[$name])) {

                throw new \InvalidArgumentException;

            }

            // Check if the invariant holds
            ' . $this->createInvariantCall($invariantUsed) . '

            // Now check what kind of visibility we would have
            $attribute = $this->attributes[$name];
            switch ($attribute["visibility"]) {

                case "protected" :

                    if (is_subclass_of(get_called_class(), "' . $classDefinition->name . '")) {

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
            ' . $this->createInvariantCall($invariantUsed) . '
        }
        ';


        // Create all the methods.
        // To do so we need the list of function definitions.
        $functionDefinitionList = $classDefinition->functionDefinitions;

        // Iterate over them and build up the methods.
        $functionIterator = $functionDefinitionList->getIterator();
        for ($i = 0; $i < $functionIterator->count(); $i++) {

            $functionDefinition = $functionIterator->current();

            // Create the method header
            $fileContent .= $functionDefinition->docBlock;
            $fileContent .= $functionDefinition->visibility . ' function ' . $functionDefinition->name;

            // Iterate over all parameters and create the parameter string.
            // We will create two strings, one for calling the method and one for defining it.
            $parameterCallString = '';
            $parameterDefineString = '';
            $parameterIterator = $functionDefinition->parameterDefinitions->getIterator();
            for ($k = 0; $k < $parameterIterator->count(); $k++) {

                // Our parameter
                $parameter = $parameterIterator->current();

                // Fill our strings
                $parameterDefineString .= $parameter->type . ' ' . $parameter->name . ', ';
                $parameterCallString .= $parameter->name . ', ';

                // Next assertion please
                $parameterIterator->next();
            }

            // Don't forget to cut the trailing commata from the strings
            $parameterCallString = trim(substr($parameterCallString, 0, strlen($parameterCallString) - 2));
            $parameterDefineString = trim(substr($parameterDefineString, 0, strlen($parameterDefineString) - 2));

            // We have to sanitize the strings, and make sure there are brackets enclosing them.
            if (strpos($parameterDefineString, '(') !== 0) {

                $parameterDefineString = '(' . $parameterDefineString;
            }
            if (strrpos($parameterDefineString, ')') !== strlen($parameterDefineString) - 1) {

                $parameterDefineString .= ')';
            }

            if (strpos($parameterCallString, '(') !== 0) {

                $parameterCallString = '(' . $parameterCallString;
            }
            if (strrpos($parameterCallString, ')') !== strlen($parameterCallString) - 1) {

                $parameterCallString .= ')';
            }

            $fileContent .= $parameterDefineString . '{';

            // First of all check if our invariant holds, but only if we need it
            if ($functionDefinition->visibility !== 'private') {

                $fileContent .= $this->createInvariantCall($invariantUsed);
            }

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

            // Now call the original method itself
            if ($functionDefinition->isStatic) {

                $fileContent .= PBC_KEYWORD_RESULT . ' = self::' . $functionDefinition->name . PBC_ORIGINAL_FUNCTION_SUFFIX .
                    $parameterCallString . ';';

            } else {

                $fileContent .= PBC_KEYWORD_RESULT . ' = $this->' . $functionDefinition->name . PBC_ORIGINAL_FUNCTION_SUFFIX .
                    $parameterCallString . ';';
            }

            // Iterate over all postconditions
            $assertionIterator = $functionDefinition->postConditions->getIterator();
            for ($k = 0; $k < $assertionIterator->count(); $k++) {

                $fileContent .= $this->createAroundAdviceCode($assertionIterator->current(), $functionDefinition->name, 'BrokenPostConditionException');

                // Next assertion please
                $assertionIterator->next();
            }

            // Last of all check if our invariant holds, but only if we need it
            if ($functionDefinition->visibility !== 'private') {

                $fileContent .= $this->createInvariantCall($invariantUsed);
            }

            // If we passed every check we can return the result
            $fileContent .= 'return ' . PBC_KEYWORD_RESULT . ';}';

            // Now we have to create the original function
            if ($functionDefinition->isStatic) {

                $fileContent .= 'final private static function ';

            } else {

                $fileContent .= 'final private function ';
            }
            $fileContent .= $functionDefinition->name . PBC_ORIGINAL_FUNCTION_SUFFIX . $parameterCallString . '{';
            $fileContent .= $functionDefinition->body . '}';

            // Move the iterator
            $functionIterator->next();
        }

        // Make the final closing bracket
        $fileContent .= '}';

        // Return if we succeeded or not
        return (boolean)file_put_contents($targetFileName, $fileContent);
    }

    /**
     * @return string
     */
    private function createInvariantCall($invariantUsed)
    {
        if ($invariantUsed === true) {

            $code = 'list(, $caller) = debug_backtrace(false);
        if (isset($caller["class"]) && $caller["class"] !== __CLASS__) {

            $this->' . PBC_CLASS_INVARIANT_NAME . '();
        }
        ';

        } else {

            $code = '';
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

        return $this->cacheMap[$className]['path'];
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