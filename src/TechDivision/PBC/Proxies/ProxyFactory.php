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
use TechDivision\PBC\Entities\Lists\TypedListList;
use TechDivision\PBC\Interfaces\Assertion;
use TechDivision\PBC\Entities\Definitions\ClassDefinition;
use TechDivision\PBC\Parser\FileParser;
use TechDivision\PBC\Interfaces\PBCCache;
use TechDivision\PBC\Config;

/**
 * Class ProxyFactory
 */
class ProxyFactory
{

    /**
     * @var \TechDivision\PBC\Interfaces\PBCCache
     */
    private $cache;

    /**
     * @var array
     */
    private $config;

    /**
     * @param PBCCache $cache
     */
    public function __construct(PBCCache $cache)
    {
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
        $fileMap = $this->cache->getFiles();
        if (isset($fileMap[$className]['path']) === false) {

            return false;
        }

        // We know the class and we know the file it is in, so get our FileParser and have a blast
        $fileParser = new FileParser();
        $fileDefinition = $fileParser->getDefinitionFromFile($fileMap[$className]['path']);

        // So we got our FileDefinition, now lets check if there are multiple classes in there.
        // Iterate over all classes within the FileDefinition and create a file for each of them
        $classIterator = $fileDefinition->classDefinitions->getIterator();
        for ($k = 0; $k < $classIterator->count(); $k++) {

            $classDefinition = $classIterator->current();
            $filePath = $this->createProxyFilePath($fileMap[$className]['path'], $classDefinition->name);

            $tmp = $this->createFileFromDefinitions($filePath, $fileDefinition, $classDefinition);

            if ($tmp === true) {

                // Now get our new file into the cacheMap
                $this->cache->add($className, $classDefinition, $filePath);

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
     * @throws \Exception|PHPParser_Error
     */
    private function createFileFromDefinitions($targetFileName, FileDefinition $fileDefinition, ClassDefinition $classDefinition)
    {
        // This variable is used to determine if we need an invariant, as we might as well not.
        $invariantUsed = false;
        // Before using the definition we have to finalize it
        $classDefinition->finalize();
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

        $fileContent .= '
        {
        ';

        // Lets fill in all the constants (if any).
        foreach ($classDefinition->constants as $constant => $value) {

            $fileContent .= ' const ' . $constant . ' = ' . $value . ';
            ';
        }

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

            $fileContent .= $this->createInvariantCode($classDefinition);

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
            $parameterCallString = array();
            $parameterDefineString = array();
            $parameterIterator = $functionDefinition->parameterDefinitions->getIterator();
            for ($k = 0; $k < $parameterIterator->count(); $k++) {

                // Our parameter
                $parameter = $parameterIterator->current();

                // Fill our strings
                $parameterDefineString[] = $parameter->getString('definition');
                $parameterCallString[] = $parameter->getString('call');

                // Next assertion please
                $parameterIterator->next();
            }

            // Explode to insert commas
            $parameterCallString = implode(', ', $parameterCallString);
            $parameterDefineString = implode(', ', $parameterDefineString);

            $fileContent .= '(' . $parameterDefineString . ') {';

            // First of all check if our invariant holds, but only if we need it
            if ($functionDefinition->visibility !== 'private') {

                $fileContent .= $this->createInvariantCall($invariantUsed);
            }

            // Here we need the combined preconditions, so gather them first
            $preConditions = $functionDefinition->ancestralPreConditions;
            $preConditions->add($functionDefinition->preConditions);

            // And now let our helper method render the code
            $fileContent .= $this->generateAroundAdviceCode($preConditions, $functionDefinition->name, 'precondition');

            // Do we have to keep an instance of $this to compare with old later?
            if ($functionDefinition->usesOld === true) {

                $fileContent .= '$this->' . PBC_KEYWORD_OLD . ' = clone $this;';
            }

            // Now call the original method itself
            if ($functionDefinition->isStatic) {

                $fileContent .= PBC_KEYWORD_RESULT . ' = self::' . $functionDefinition->name . PBC_ORIGINAL_FUNCTION_SUFFIX .
                    '(' . $parameterCallString . ');';

            } else {

                $fileContent .= PBC_KEYWORD_RESULT . ' = $this->' . $functionDefinition->name . PBC_ORIGINAL_FUNCTION_SUFFIX .
                    '(' . $parameterCallString . ');';
            }

            // Here we need the combined preconditions, so gather them first
            $postConditions = $functionDefinition->ancestralPostConditions;
            $postConditions->add($functionDefinition->postConditions);

            // And now let our helper method render the code
            $fileContent .= $this->generateAroundAdviceCode($postConditions, $functionDefinition->name, 'postcondition');

            // Last of all check if our invariant holds, but only if we need it
            if ($functionDefinition->visibility !== 'private') {

                $fileContent .= $this->createInvariantCall($invariantUsed);
            }

            // If we passed every check we can return the result
            $fileContent .= 'return ' . PBC_KEYWORD_RESULT . ';}';

            // Now we have to create the original function
            if ($functionDefinition->isStatic) {

                $fileContent .= 'private static function ';

            } else {

                $fileContent .= 'private function ';
            }
            $fileContent .= $functionDefinition->name . PBC_ORIGINAL_FUNCTION_SUFFIX . '(' . $parameterCallString . ') {';
            $fileContent .= $functionDefinition->body . '}';
            // Move the iterator
            $functionIterator->next();
        }

        // Make the final closing bracket
        $fileContent .= '}';

        // PrettyPrint it so humans can read it
        $parser = new \PHPParser_Parser(new \PHPParser_Lexer);
        $prettyPrinter = new \PHPParser_PrettyPrinter_Default;

        try {
            // parse
            $stmts = $parser->parse($fileContent);

            $fileContent = '<?php ' . $prettyPrinter->prettyPrint($stmts);

        } catch (PHPParser_Error $e) {

            throw $e;
        }

        // Return if we succeeded or not
        return (boolean)file_put_contents($targetFileName, $fileContent);
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

                        $exception = 'BrokenPreConditionException';
                        break;

                    case 'postcondition':

                        $exception = 'BrokenPostConditionException';
                        break;

                    case 'invariant':

                        $exception = 'BrokenInvariantException';
                        break;

                    default:

                        $exception = '\Exception';
                        break;
                }
                // Create the code
                $code .= 'throw new ' . $exception . '(\'' . $message . '\');';

                break;

            case 'logging':

                // Create the code
                $code .= '$logger = new ' . $this->config['logger'] . '();
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

        // Preconditions need or-ed conditions so we make sure only one conditionlist gets checked
        if ($type === 'precondition') {

            $code = '$passedOne = false;
                $failedAssertion = array();';

        } else {

            $code = '';
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

        return $code;
    }

    /**
     * @param ClassDefinition $classDefinition
     * @return string
     */
    private function createInvariantCode(ClassDefinition $classDefinition)
    {
        // We might have ancestral, interface and direct invariants, so collect them first so we can better handle them later
        $invariants = $classDefinition->ancestralInvariants;
        $invariants->add($classDefinition->invariantConditions);

        $code = '';

        $invariantIterator = $invariants->getIterator();
        for ($i = 0; $i < $invariantIterator->count(); $i++) {

            // Create the inner loop for the different assertions
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

            // increment the outer loop
            $invariantIterator->next();
        }

        return $code;
    }

    /**
     * @param $invariantUsed
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
     * @param $className
     *
     * @return mixed
     */
    public function getProxyFileName($className)
    {
        $cacheMap = $this->cache->get();
        if (!isset($cacheMap[$className]) || !isset($cacheMap[$className]['path'])) {

            return false;
        }

        return $cacheMap[$className]['path'];
    }
}