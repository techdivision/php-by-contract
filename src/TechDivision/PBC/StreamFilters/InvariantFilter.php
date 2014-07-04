<?php
/**
 * File containing the InvariantFilter class
 *
 * PHP version 5
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage StreamFilters
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */

namespace TechDivision\PBC\StreamFilters;

use TechDivision\PBC\Entities\Lists\AttributeDefinitionList;
use TechDivision\PBC\Entities\Lists\TypedListList;
use TechDivision\PBC\Exceptions\GeneratorException;

/**
 * TechDivision\PBC\StreamFilters\InvariantFilter
 *
 * This filter will buffer the input stream and add all invariant related information at prepared locations
 * (see $dependencies)
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage StreamFilters
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */
class InvariantFilter extends AbstractFilter
{

    /**
     * @const integer FILTER_ORDER Order number if filters are used as a stack, higher means below others
     */
    const FILTER_ORDER = 3;

    /**
     * @var array $dependencies Other filters on which we depend
     */
    private $dependencies = array('SkeletonFilter');

    /**
     * @var mixed $params The parameter(s) we get passed when appending the filter to a stream
     * @link http://www.php.net/manual/en/class.php-user-filter.php
     */
    public $params;

    /**
     * Will return the dependency array
     *
     * @return array
     */
    public function getDependencies()
    {
        return $this->dependencies;
    }

    /**
     * Will return the order number the concrete filter has been constantly assigned
     *
     * @return integer
     */
    public function getFilterOrder()
    {
        return self::FILTER_ORDER;
    }

    /**
     * Not implemented yet
     *
     * @throws \Exception
     *
     * @return void
     */
    public function dependenciesMet()
    {
        throw new \Exception();
    }

    /**
     * The main filter method.
     * Implemented according to \php_user_filter class. Will loop over all stream buckets, buffer them and perform
     * the needed actions.
     *
     * @param resource $in        Incoming bucket brigade we need to filter
     * @param resource $out       Outgoing bucket brigade with already filtered content
     * @param integer  &$consumed The count of altered characters as buckets pass the filter
     * @param boolean  $closing   Is the stream about to close?
     *
     * @throws \TechDivision\PBC\Exceptions\GeneratorException
     *
     * @return integer
     *
     * @link http://www.php.net/manual/en/php-user-filter.filter.php
     */
    public function filter($in, $out, &$consumed, $closing)
    {
        $structureDefinition = $this->params;

        // After iterate over the attributes and build up our array of attributes we have to include in our
        // checking mechanism.
        $obsoleteProperties = array();
        $propertyReplacements = array();
        $iterator = $structureDefinition->getAttributeDefinitions()->getIterator();
        for ($i = 0; $i < $iterator->count(); $i++) {

            // Get the current attribute for more easy access
            $attribute = $iterator->current();

            // Only enter the attribute if it is used in an invariant and it is not private
            if ($attribute->inInvariant && $attribute->visibility !== 'private') {

                // Build up our regex expression to filter them out
                $obsoleteProperties[] = '/' . $attribute->visibility . '.*?\\' . $attribute->name . '/';
                $propertyReplacements[] = 'private ' . $attribute->name;
            }

            // Move the iterator
            $iterator->next();
        }

        // Get our buckets from the stream
        $functionHook = '';
        while ($bucket = stream_bucket_make_writeable($in)) {

            // We only have to do that once!
            if (empty($functionHook)) {

                $functionHook = PBC_FUNCTION_HOOK_PLACEHOLDER . PBC_PLACEHOLDER_CLOSE;

                // Get the code for our attribute storage
                $attributeCode = $this->generateAttributeCode($structureDefinition->getAttributeDefinitions());

                // Get the code for the assertions
                $code = $this->generateFunctionCode($structureDefinition->getInvariants());

                // Insert the code
                $bucket->data = str_replace(
                    array(
                        $functionHook,
                        $functionHook
                    ),
                    array(
                        $functionHook . $attributeCode,
                        $functionHook . $code
                    ),
                    $bucket->data
                );

                // Determine if we need the __set method to be injected
                if ($structureDefinition->getFunctionDefinitions()->entryExists('__set')) {

                    // Get the code for our __set() method
                    $setCode = $this->generateSetCode($structureDefinition->hasParents(), true);
                    $bucket->data = str_replace(
                        PBC_METHOD_INJECT_PLACEHOLDER . '__set' . PBC_PLACEHOLDER_CLOSE,
                        $setCode,
                        $bucket->data
                    );

                } else {

                    $setCode = $this->generateSetCode($structureDefinition->hasParents());
                    $bucket->data = str_replace(
                        $functionHook,
                        $functionHook . $setCode,
                        $bucket->data
                    );
                }

                // Determine if we need the __get method to be injected
                if ($structureDefinition->getFunctionDefinitions()->entryExists('__get')) {

                    // Get the code for our __set() method
                    $getCode = $this->generateGetCode($structureDefinition->hasParents(), true);
                    $bucket->data = str_replace(
                        PBC_METHOD_INJECT_PLACEHOLDER . '__get' . PBC_PLACEHOLDER_CLOSE,
                        $getCode,
                        $bucket->data
                    );

                } else {

                    $getCode = $this->generateGetCode($structureDefinition->hasParents());
                    $bucket->data = str_replace(
                        $functionHook,
                        $functionHook . $getCode,
                        $bucket->data
                    );
                }
            }

            // We need the code to call the invariant
            $this->injectInvariantCall($bucket->data);

            // Remove all the properties we will take care of with our magic setter and getter
            $bucket->data = preg_replace($obsoleteProperties, $propertyReplacements, $bucket->data, 1);

            // Tell them how much we already processed, and stuff it back into the output
            $consumed += $bucket->datalen;
            stream_bucket_append($out, $bucket);
        }

        return PSFS_PASS_ON;
    }

    /**
     * Will generate the code needed to for managing the attributes in regards to invariants related to them
     *
     * @param \TechDivision\PBC\Entities\Lists\AttributeDefinitionList $attributeDefinitions Defined attributes
     *
     * @return string
     */
    private function generateAttributeCode(AttributeDefinitionList $attributeDefinitions)
    {
        // We should create attributes to store our attribute types
        $code = '/**
            * @var array
            */
            private $' . PBC_ATTRIBUTE_STORAGE . ' = array(';

        // After iterate over the attributes and build up our array
        $iterator = $attributeDefinitions->getIterator();
        for ($i = 0; $i < $iterator->count(); $i++) {

            // Get the current attribute for more easy access
            $attribute = $iterator->current();

            // Only enter the attribute if it is used in an invariant and it is not private
            if ($attribute->inInvariant && $attribute->visibility !== 'private') {

                $code .= '"' . substr($attribute->name, 1) . '"';
                $code .= ' => array("visibility" => "' . $attribute->visibility . '", ';

                // Now check if we need any keywords for the variable identity
                if ($attribute->isStatic) {

                    $code .= '"static" => true';
                } else {

                    $code .= '"static" => false';
                }
                $code .= '),';
            }

            // Move the iterator
            $iterator->next();
        }
        $code .= ');
        ';

        return $code;
    }

    /**
     * Will generate the code of the magic __set() method needed to check invariants related to member variables
     *
     * @param boolean $hasParents Does this structure have parents
     * @param boolean $injected   Will the created method be injected or is it a stand alone method?
     *
     * @return string
     */
    private function generateSetCode($hasParents, $injected = false)
    {

        // We only need the method header if we don't inject
        if ($injected === false) {

            $code = '/**
             * Magic function to forward writing property access calls if within visibility boundaries.
             *
             * @throws \Exception
             */
            public function __set($name, $value)
            {';
        } else {

            $code = '';
        }

        $code .= PBC_CONTRACT_CONTEXT . ' = \TechDivision\PBC\ContractContext::open();
        // Does this property even exist? If not, throw an exception
            if (!isset($this->' . PBC_ATTRIBUTE_STORAGE . '[$name])) {';

        if ($hasParents) {

            $code .= 'return parent::__set($name, $value);';
        } else {

            $code .= 'if (property_exists($this, $name)) {' .

                PBC_FAILURE_VARIABLE . ' = "accessing $name in an invalid way";' .
                PBC_PROCESSING_PLACEHOLDER . 'InvalidArgumentException' . PBC_PLACEHOLDER_CLOSE .
                '\TechDivision\PBC\ContractContext::close();
                return false;
                } else {' .

                PBC_FAILURE_VARIABLE . ' = "accessing $name as it does not exist";' .
                PBC_PROCESSING_PLACEHOLDER . 'MissingPropertyException' . PBC_PLACEHOLDER_CLOSE .
                '\TechDivision\PBC\ContractContext::close();
                return false;
                }';
        }

        $code .= '}
        // Check if the invariant holds
            ' . PBC_INVARIANT_PLACEHOLDER . PBC_PLACEHOLDER_CLOSE .
            '// Now check what kind of visibility we would have
            $attribute = $this->' . PBC_ATTRIBUTE_STORAGE . '[$name];
            switch ($attribute["visibility"]) {

                case "protected" :

                    if (is_subclass_of(get_called_class(), __CLASS__)) {

                        $this->$name = $value;

                    } else {' .

            PBC_FAILURE_VARIABLE . ' = "accessing $name in an invalid way";' .
            PBC_PROCESSING_PLACEHOLDER . 'InvalidArgumentException' . PBC_PLACEHOLDER_CLOSE .
            '\TechDivision\PBC\ContractContext::close();
            return false;
            }
                    break;

                case "public" :

                    $this->$name = $value;
                    break;

                default :' .

            PBC_FAILURE_VARIABLE . ' = "accessing $name in an invalid way";' .
            PBC_PROCESSING_PLACEHOLDER . 'InvalidArgumentException' . PBC_PLACEHOLDER_CLOSE .
            '\TechDivision\PBC\ContractContext::close();
            return false;
            break;
            }

            // Check if the invariant holds
            ' . PBC_INVARIANT_PLACEHOLDER . PBC_PLACEHOLDER_CLOSE .
            '\TechDivision\PBC\ContractContext::close();';

        // We do not need the method encasing brackets if we inject
        if ($injected === false) {

            $code .= '}';
        }

        return $code;
    }

    /**
     * Will generate the code of the magic __get() method needed to access member variables which are hidden
     * in order to force the usage of __set()
     *
     * @param boolean $hasParents Does this structure have parents
     * @param boolean $injected   Will the created method be injected or is it a stand alone method?
     *
     * @return string
     */
    private function generateGetCode($hasParents, $injected = false)
    {

        // We only need the method header if we don't inject
        if ($injected === false) {

            $code = '/**
         * Magic function to forward reading property access calls if within visibility boundaries.
         *
         * @throws \Exception
         */
        public function __get($name)
        {';
        } else {

            $code = '';
        }
        $code .=
            '// Does this property even exist? If not, throw an exception
            if (!isset($this->' . PBC_ATTRIBUTE_STORAGE . '[$name])) {';

        if ($hasParents) {

            $code .= 'return parent::__get($name);';
        } else {

            $code .= 'if (property_exists($this, $name)) {' .

                PBC_FAILURE_VARIABLE . ' = "accessing $name in an invalid way";' .
                PBC_PROCESSING_PLACEHOLDER . 'InvalidArgumentException' . PBC_PLACEHOLDER_CLOSE .
                '\TechDivision\PBC\ContractContext::close();
                return false;
                } else {' .

                PBC_FAILURE_VARIABLE . ' = "accessing $name as it does not exist";' .
                PBC_PROCESSING_PLACEHOLDER . 'MissingPropertyException' . PBC_PLACEHOLDER_CLOSE .
                '\TechDivision\PBC\ContractContext::close();
                return false;
                }';
        }

        $code .= '}

        // Now check what kind of visibility we would have
        $attribute = $this->' . PBC_ATTRIBUTE_STORAGE . '[$name];
        switch ($attribute["visibility"]) {

            case "protected" :

                if (is_subclass_of(get_called_class(), __CLASS__)) {

                    return $this->$name;

                } else {' .

            PBC_FAILURE_VARIABLE . ' = "accessing $name in an invalid way";' .
            PBC_PROCESSING_PLACEHOLDER . 'InvalidArgumentException' . PBC_PLACEHOLDER_CLOSE .
            '\TechDivision\PBC\ContractContext::close();
            return false;}
                break;

            case "public" :

                return $this->$name;
                break;

            default :' .

            PBC_FAILURE_VARIABLE . ' = "accessing $name in an invalid way";' .
            PBC_PROCESSING_PLACEHOLDER . 'InvalidArgumentException' . PBC_PLACEHOLDER_CLOSE .
            '\TechDivision\PBC\ContractContext::close();
            return false;
            break;
        }';

        // We do not need the method encasing brackets if we inject
        if ($injected === false) {

            $code .= '}';
        }

        return $code;
    }

    /**
     * Will inject the call to the invariant checking method at encountered placeholder strings within the passed
     * bucket data
     *
     * @param string &$bucketData Payload of the currently filtered bucket
     *
     * @return boolean
     */
    private function injectInvariantCall(& $bucketData)
    {
        $code = 'if (' . PBC_CONTRACT_CONTEXT . ' === true) {
            $this->' . PBC_CLASS_INVARIANT_NAME . '(__METHOD__);}';

        // Still here? Then inject the clone statement to preserve an instance of the object prior to our call.
        $bucketData = str_replace(
            PBC_INVARIANT_PLACEHOLDER . PBC_PLACEHOLDER_CLOSE,
            $code,
            $bucketData
        );

        // Still here? We encountered no error then.
        return true;
    }

    /**
     * Will generate the code needed to enforce made invariant assertions
     *
     * @param \TechDivision\PBC\Entities\Lists\TypedListList $assertionLists List of assertion lists
     *
     * @return string
     */
    private function generateFunctionCode(TypedListList $assertionLists)
    {
        $code = 'protected function ' . PBC_CLASS_INVARIANT_NAME . '($callingMethod) {' .
            PBC_CONTRACT_CONTEXT . ' = \TechDivision\PBC\ContractContext::open();if (' . PBC_CONTRACT_CONTEXT . ') {';

        $invariantIterator = $assertionLists->getIterator();
        for ($i = 0; $i < $invariantIterator->count(); $i++) {

            // Create the inner loop for the different assertions
            if ($invariantIterator->current()->count() !== 0) {

                $assertionIterator = $invariantIterator->current()->getIterator();
                $codeFragment = array();

                for ($j = 0; $j < $assertionIterator->count(); $j++) {

                    $codeFragment[] = $assertionIterator->current()->getString();

                    $assertionIterator->next();
                }
                $code .= 'if (!((' . implode(') && (', $codeFragment) . '))){' .
                    PBC_FAILURE_VARIABLE . ' = \'(' . str_replace(
                        '\'',
                        '"',
                        implode(') && (', $codeFragment)
                    ) . ')\';' .
                    PBC_PROCESSING_PLACEHOLDER . 'invariant' . PBC_PLACEHOLDER_CLOSE . '}';
            }
            // increment the outer loop
            $invariantIterator->next();
        }

        $code .= '}\TechDivision\PBC\ContractContext::close();}';

        return $code;
    }
}
