<?php
/**
 * TechDivision\PBC\StreamFilters\InvariantFilter
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 */

namespace TechDivision\PBC\StreamFilters;

use TechDivision\PBC\Entities\Lists\AttributeDefinitionList;
use TechDivision\PBC\Exceptions\GeneratorException;
use TechDivision\PBC\Interfaces\StructureDefinition;

/**
 * @package     TechDivision\PBC
 * @subpackage  StreamFilters
 * @copyright   Copyright (c) 2013 <info@techdivision.com> - TechDivision GmbH
 * @license     http://opensource.org/licenses/osl-3.0.php
 *              Open Software License (OSL 3.0)
 * @author      Bernhard Wick <b.wick@techdivision.com>
 */
class InvariantFilter
{
    /**
     * @const   int
     */
    const FILTER_ORDER = 3;

    /**
     * @var array
     */
    private $dependencies = array('SkeletonFilter');

    /**
     * @var StructureDefinition
     */
    public $params;

    /**
     * @return array
     */
    public function getDependencies()
    {
        return $this->dependencies;
    }

    /**
     * @return int
     */
    public function getFilterOrder()
    {
        return self::FILTER_ORDER;
    }

    /**
     * @throws \Exception
     */
    public function dependenciesMet()
    {
        throw new \Exception();
    }

    /**
     * @param $in
     * @param $out
     * @param $consumed
     * @param $closing
     * @return int
     * @throws GeneratorException
     */
    public function filter($in, $out, &$consumed, $closing)
    {
       /* $structureDefinition = $this->params;

        // As we have to make severe changes to the system we might ensure we need to first.
        // If there is no invariant for this or any ancestral structures we might skip this step entirely.
        $invariants = $structureDefinition->getInvariants();
        if ($invariants->count() === 0) {

            // Nothing to do here
            return PSFS_PASS_ON;
        }

        // Get our buckets from the stream
        $functionHook = '';
        while ($bucket = stream_bucket_make_writeable($in)) {

            // Get the tokens
            $tokens = token_get_all($bucket->data);

            // Go through the tokens and check what we found
            $tokensCount = count($tokens);
            for ($i = 0; $i < $tokensCount; $i++) {

                // We need something to hook into, right after class header seems fine
                if (is_array($tokens[$i]) && $tokens[$i][0] === T_CLASS) {

                    for ($j = $i; $j < $tokensCount; $j++) {

                        if (is_array($tokens[$j])) {

                            $functionHook .= $tokens[$j][1];

                        } else {

                            $functionHook .= $tokens[$j];
                        }

                        // If we got the opening bracket we can break
                        if ($tokens[$j] === '{') {

                            break;
                        }
                    }

                    // If the function hook is empty we failed and should stop what we are doing
                    if (empty($functionHook)) {

                        throw new GeneratorException();
                    }
                }

                // Get the code for our attribute storage
               // $attributeCode = $this->generateAttributeCode($structureDefinition->attributeDefinitions);

                // Get the code for our __set() method
               // $setCode = $this->generateSetCode($structureDefinition->hasParents());

                // Get the code for our __get() method
                //$getCode = $this->generateGetCode($structureDefinition->hasParents());

                // Get the code for the assertions
                //$code = $this->generateFunctionCode($invariants);

                // Insert the code
                /*$bucket->data = str_replace($functionHook, array($functionHook . $attributeCode,
                    $functionHook . $setCode,
                    $functionHook . $getCode,
                    $functionHook . $code), $bucket->data);*//*

                // "Destroy" code and function definition
                $code = null;
                $structureDefinition = null;

            }

            // Tell them how much we already processed, and stuff it back into the output
            $consumed += $bucket->datalen;
            stream_bucket_append($out, $bucket);
        }*/

        return PSFS_PASS_ON;
    }

    /**
     * @param AttributeDefinitionList $attributeDefinitions
     * @return string
     */
    private function generateAttributeCode(AttributeDefinitionList $attributeDefinitions)
    {
        // We should create attributes to store our attribute types
        $code =
            '/**
            * @var array
            */
            private $attributes = array(';

        // After iterate over the attributes and build up our array
        $iterator = $attributeDefinitions->getIterator();
        for ($i = 0; $i < $iterator->count(); $i++) {

            // Get the current attribute for more easy access
            $attribute = $iterator->current();

            $code .= '"' . substr($attribute->name, 1) . '"';
            $code .= ' => array("visibility" => "' . $attribute->visibility . '", ';

            // Now check if we need any keywords for the variable identity
            if ($attribute->isStatic) {

                $code .= '"static" => true';

            } else {

                $code .= '"static" => false';
            }
            $code .= '),';

            // Move the iterator
            $iterator->next();
        }
        $code .= ');
        ';

        // After that we should enter all the other attributes
        $iterator = $attributeDefinitions->getIterator();
        for ($i = 0; $i < $iterator->count(); $i++) {

            // Get the current attribute for more easy access
            $attribute = $iterator->current();

            $code .= 'private ';

            // Now check if we need any keywords for the variable identity
            if ($attribute->isStatic) {

                $code .= 'static ';
            }

            $code .= $attribute->name;

            // Do we have a default value
            if ($attribute->defaultValue !== null) {

                $code .= ' = ' . $attribute->defaultValue;
            }

            $code .= ';';

            // Move the iterator
            $iterator->next();
        }

        return $code;
    }

    /**
     * @param $hasParents
     * @return string
     */
    private function generateSetCode($hasParents)
    {
        $code = '/**
         * Magic function to forward writing property access calls if within visibility boundaries.
         *
         * @throws InvalidArgumentException
         *//*
        public function __set($name, $value)
        {
            // Does this property even exist? If not, throw an exception
            if (!isset($this->attributes[$name])) {';

        if ($hasParents) {

            $code .= 'return parent::__set($name, $value);';

        } else {

            $code .= 'throw new \InvalidArgumentException;';
        }

        $code .= '}}';

        return $code;
    }

    /**
     * @param $hasParents
     * @return string
     */
    private function generateGetCode($hasParents)
    {
        $code = '/**
         * Magic function to forward reading property access calls if within visibility boundaries.
         *
         * @throws InvalidArgumentException
         *//*
        public function __get($name)
        {
            // Does this property even exist? If not, throw an exception
            if (!isset($this->attributes[$name])) {';

        if ($hasParents) {

            $code .= 'return parent::__get($name);';

        } else {

            $code .= 'throw new \InvalidArgumentException;';
        }

        $code .= '}}';

        return $code;
    }

    /**
     * @param TypedListList $assertionLists
     * @return string
     */
    private function generateFunctionCode(TypedListList $assertionLists)
    {
        $code = 'private function ' . PBC_CLASS_INVARIANT_NAME . '() {';

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
                    PBC_FAILURE_VARIABLE . ' = \'(' . str_replace('\'', '"', implode(') && (', $codeFragment)) . ')\';' .
                    PBC_PROCESSING_PLACEHOLDER . 'invariant' . PBC_PLACEHOLDER_CLOSE . '}';
            }
            // increment the outer loop
            $invariantIterator->next();
        }

        $code .= '}';

        return $code;
    }
} 