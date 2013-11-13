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
        //stream_filter_append($res, 'InvariantFilter', STREAM_FILTER_WRITE, $structureDefinition);
        //stream_filter_append($res, 'ProcessingFilter', STREAM_FILTER_WRITE, $this->config);
        //stream_filter_append($res, 'BeautifyFilter', STREAM_FILTER_WRITE, $this->config);

        fwrite($res, file_get_contents($fileDefinition->path . DIRECTORY_SEPARATOR . $fileDefinition->name));


/*
        if ($structureDefinition->invariantConditions->isEmpty() === false || $structureDefinition->extends !== '') {

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
        $fileContent .= 'use TechDivision\PBC\Exceptions\BrokenPreconditionException;
        use TechDivision\PBC\Exceptions\BrokenPostconditionException;
        use TechDivision\PBC\Exceptions\BrokenInvariantException;
        ';

        // Also include the use statements that where already present in the source file
        foreach ($fileDefinition->usedNamespaces as $usedNamespace) {

            $fileContent .= 'use ' . $usedNamespace . ';
            ';
        }

        // Next build up the class header
        $fileContent .= $structureDefinition->docBlock;

        // Now check if we need any keywords for the class identity
        if ($structureDefinition->isFinal) {

            $fileContent .= 'final ';
        }
        if ($structureDefinition->isAbstract) {

            $fileContent .= 'abstract ';
        }

        $fileContent .= 'class ' . $structureDefinition->name;

        // Add any parent class or interfaces there might be.
        if ($structureDefinition->extends !== '') {

            $fileContent .= ' extends ' . $structureDefinition->extends;
        }

        if (!empty($structureDefinition->implements)) {

            $fileContent .= ' implements ' . implode(', ', $structureDefinition->implements);
        }

        $fileContent .= '
        {
        ';

        // Lets fill in all the constants (if any).
        foreach ($structureDefinition->constants as $constant => $value) {

            $fileContent .= ' const ' . $constant . ' = ' . $value . ';
            ';
        }

        // We need an attribute to check if we are in an observable state
        $fileContent .=
            '/**
            * @var int
            *//*
            protected $' . PBC_CONTRACT_DEPTH . ' = 0;';

        $fileContent .=
            '/**
            * @var mixed
            *//*
            private $' . PBC_KEYWORD_OLD . ';';

        // We should create attributes to store our attribute types
        $fileContent .=
            '/**
            * @var array
            *//*
            private $attributes = array(';

        // After iterate over the attributes and build up our array
        $iterator = $structureDefinition->attributeDefinitions->getIterator();
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
        $iterator = $structureDefinition->attributeDefinitions->getIterator();
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
            $fileContent .= 'protected function ' . PBC_CLASS_INVARIANT_NAME . '() {';

            $fileContent .= $this->createInvariantCode($structureDefinition);

            $fileContent .= '}
        ';
        }

        // Now we need our magic __set method to catch anybody who wants to change the attributes.
        // If we would not do so a client could break the class without triggering the invariant.
        $fileContent .= '/**
         * Magic function to forward writing property access calls if within visibility boundaries.
         *
         * @throws InvalidArgumentException
         *//*
        public function __set($name, $value)
        {
            // Does this property even exist? If not, throw an exception
            if (!isset($this->attributes[$name])) {';

        if ($structureDefinition->extends !== '') {

            $fileContent .= 'return parent::__set($name, $value);';

        } else {

            $fileContent .= 'throw new \InvalidArgumentException;';
        }

        $fileContent .= '}

            // Check if the invariant holds
            ' . $this->createInvariantCall($invariantUsed, "entry") . '

            // Now check what kind of visibility we would have
            $attribute = $this->attributes[$name];
            switch ($attribute["visibility"]) {

                case "protected" :

                    if (is_subclass_of(get_called_class(), __CLASS__)) {

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
            ' . $this->createInvariantCall($invariantUsed, "exit") . '
        }
        ';

        // Now we need our magic __get method. We do not check the invariant for reading from an attribute
        // but we already protected the attributes for using __set, so this step is necessary.
        $fileContent .= '/**
         * Magic function to forward reading property access calls if within visibility boundaries.
         *
         * @throws InvalidArgumentException
         *//*
        public function __get($name)
        {
            // Does this property even exist? If not, throw an exception
            if (!isset($this->attributes[$name])) {';

        if ($structureDefinition->extends !== '') {

            $fileContent .= 'return parent::__get($name);';

        } else {

            $fileContent .= 'throw new \InvalidArgumentException;';
        }

        $fileContent .= '}

            // Now check what kind of visibility we would have
            $attribute = $this->attributes[$name];
            switch ($attribute["visibility"]) {

                case "protected" :

                    if (is_subclass_of(get_called_class(), __CLASS__)) {

                        return $this->$name;

                    } else {

                        throw new \InvalidArgumentException;
                    }
                    break;

                case "public" :

                    return $this->$name;
                    break;

                default :

                    throw new \InvalidArgumentException;
                    break;
            }
        }
        ';

        // Create all the methods.
        // To do so we need the list of function definitions.
        $functionDefinitionList = $structureDefinition->functionDefinitions;

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

                $fileContent .= $this->createInvariantCall($invariantUsed, "entry");
            }

            // Here we need the combined preconditions, so gather them first
            $preconditions = $functionDefinition->ancestralPreconditions;
            $preconditions->add($functionDefinition->preconditions);

            // And now let our helper method render the code
            $fileContent .= $this->generateAroundAdviceCode($preconditions, $functionDefinition->name, 'precondition');

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
            $postconditions = $functionDefinition->ancestralPostconditions;
            $postconditions->add($functionDefinition->postconditions);

            // And now let our helper method render the code
            $fileContent .= $this->generateAroundAdviceCode($postconditions, $functionDefinition->name, 'postcondition');

            // Last of all check if our invariant holds, but only if we need it
            if ($functionDefinition->visibility !== 'private') {

                $fileContent .= $this->createInvariantCall($invariantUsed, "exit");
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

        */// Return if we succeeded or not
        return true;
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