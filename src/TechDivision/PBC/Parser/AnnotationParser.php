<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 19.06.13
 * Time: 15:15
 * To change this template use File | Settings | File Templates.
 */

namespace TechDivision\PBC\Parser;

use TechDivision\PBC\Entities\Assertions\RawAssertion;
use TechDivision\PBC\Entities\Assertions\TypedCollectionAssertion;
use TechDivision\PBC\Entities\Lists\AssertionList;
use TechDivision\PBC\Entities\Assertions\ChainedAssertion;
use TechDivision\PBC\Config;
use TechDivision\PBC\Exceptions\ParserException;

/**
 * Class AnnotationParser
 */
class AnnotationParser extends AbstractParser
{
    /**
     * @var
     */
    private $config;

    /**
     * All simple data types which are supported by PHP
     * and have a is_... function.
     *
     * @var array
     */
    private $validSimpleTypes = array(
        'array',
        'bool',
        'callable',
        'double',
        'float',
        'int',
        'integer',
        'long',
        'null',
        'numeric',
        'object',
        'real',
        'resource',
        'scalar',
        'string'
    );

    /**
     * All simple data types which are known but are aliased without an is_... function.
     *
     * @var array
     */
    private $simpleTypeMappings = array(
        'boolean' => 'bool'
    );

    public function __construct()
    {
        $config = Config::getInstance();
        $this->config = $config->getConfig('enforcement');
    }

    /**
     * @param $docBlock
     * @param $conditionKeyword
     * @return bool|AssertionList
     */
    public function getConditions($docBlock, $conditionKeyword)
    {
        // There are only 3 valid condition types
        if ($conditionKeyword !== PBC_KEYWORD_PRE && $conditionKeyword !== PBC_KEYWORD_POST
            && $conditionKeyword !== PBC_KEYWORD_INVARIANT
        ) {

            return false;
        }

        // Get our conditions
        $rawConditions = array();
        if ($conditionKeyword === PBC_KEYWORD_POST) {

            // Check if we need @return as well
            if ($this->config['enforce-default-type-safety'] === true) {

                $regex = '/' . str_replace('\\', '\\\\', $conditionKeyword) . '.+?\n|' . '@return' . '.+?\n/s';

            } else {

                $regex = '/' . str_replace('\\', '\\\\', $conditionKeyword) . '.+?\n/s';
            }

            preg_match_all($regex, $docBlock, $rawConditions);

        } elseif ($conditionKeyword === PBC_KEYWORD_PRE) {

            // Check if we need @return as well
            if ($this->config['enforce-default-type-safety'] === true) {

                $regex = '/' . str_replace('\\', '\\\\', $conditionKeyword) . '.+?\n|' . '@param' . '.+?\n/s';

            } else {

                $regex = '/' . str_replace('\\', '\\\\', $conditionKeyword) . '.+?\n/s';
            }

            preg_match_all($regex, $docBlock, $rawConditions);

        } else {

            preg_match_all('/' . str_replace('\\', '\\\\', $conditionKeyword) . '.+?\n/s', $docBlock, $rawConditions);
        }

        // Lets build up the result array
        $result = new AssertionList();
        if (empty($rawConditions) === false) {
            foreach ($rawConditions[0] as $condition) {

                $assertion = $this->parseAssertion($condition);
                if ($assertion !== false) {

                    $result->add($assertion);
                }
            }
        }

        return $result;
    }

    /**
     * @param $docString
     * @param null $usedAnnotation
     * @return bool
     */
    private function parseAssertion($docString, $usedAnnotation = null)
    {
        if ($usedAnnotation === null) {

            // We have to differ between several types of assertions, so lets check which one we got
            $annotations = array('@param', '@return', PBC_KEYWORD_POST, PBC_KEYWORD_PRE, PBC_KEYWORD_INVARIANT);

            $usedAnnotation = '';
            foreach ($annotations as $annotation) {

                if (strpos($docString, $annotation) !== false) {

                    $usedAnnotation = $annotation;
                    break;
                }
            }
        }

        // Do we have an or combinator aka |?
        if ($this->filterOrCombinator($docString)) {

            // If we got invalid arguments then we will fail
            try {

                return $this->parseChainedAssertion('|', $docString);

            } catch (\InvalidArgumentException $e) {

                return false;
            }
        }

        // If we got invalid arguments then we will fail
        try {

            $variable = $this->filterVariable($docString);
            $type = $this->filterType($docString);
            $class = $this->filterClass($docString);

        } catch (\InvalidArgumentException $e) {

            return false;
        }

        switch ($usedAnnotation) {
            // We got something which can only contain type information
            case '@param':
            case '@return':

                if ($usedAnnotation === '@return') {

                    $variable = PBC_KEYWORD_RESULT;

                }

                // Now we have to check what we got
                // First of all handle if we got a simple type
                if ($type !== false) {

                    $assertionType = 'TechDivision\PBC\Entities\Assertions\TypeAssertion';

                } elseif ($class !== false) {

                    // We might also have a typed collection
                    $type = $this->filterTypedCollection($class);
                    if ($type !== false && $variable !== false) {

                        $assertion = new TypedCollectionAssertion($variable, $type);
                        break;
                    }

                    $type = $class;
                    $assertionType = 'TechDivision\PBC\Entities\Assertions\InstanceAssertion';

                } else {

                    return false;
                }

                // We handled what kind of assertion we need, now check what we will assert
                if ($variable !== false) {

                    $assertion = new $assertionType($variable, $type);

                } elseif ($usedAnnotation === '@return') {

                    $assertion = new $assertionType(PBC_KEYWORD_RESULT, $type);

                } else {

                    return false;
                }

                break;

            // We got our own definitions. Could be a bit more complex here
            case PBC_KEYWORD_PRE:
            case PBC_KEYWORD_POST:
            case PBC_KEYWORD_INVARIANT:

                // Now we have to check what we got
                // First of all handle if we got a simple type
                if ($type !== false) {

                    $assertionType = 'TechDivision\PBC\Entities\Assertions\TypeAssertion';

                } elseif ($class !== false && !empty($class)) {

                    // We might also have a typed collection
                    $type = $this->filterTypedCollection($docString);
                    if ($type !== false && $variable !== false) {

                        $assertion = new TypedCollectionAssertion($variable, $type);
                        break;
                    }

                    $type = $class;
                    $assertionType = 'TechDivision\PBC\Entities\Assertions\InstanceAssertion';

                } else {

                    $assertion = new RawAssertion(trim(str_replace($usedAnnotation, '', $docString)));
                    break;
                }

                // We handled what kind of assertion we need, now check what we will assert
                if ($variable !== false && !empty($assertionType)) {

                    $assertion = new $assertionType($variable, $type);

                } else {

                    $assertion = new RawAssertion(trim(str_replace($usedAnnotation, '', $docString)));
                }

                break;
        }

        return $assertion;
    }

    /**
     * @param $combinator
     * @param $docString
     */
    private function parseChainedAssertion($combinator, $docString)
    {
        // Get all the parts of the string
        $assertionArray = explode(' ', $docString);

        // Check all string parts for the | character
        $combinedPart = '';
        $combinedIndex = 0;
        foreach ($assertionArray as $key => $assertionPart) {

            // Check which part contains the | but does not only consist of it
            if ($this->filterOrCombinator($assertionPart) && trim($assertionPart) !== '|') {

                $combinedPart = trim($assertionPart);
                $combinedIndex = $key;
                break;
            }
        }

        // Check if we got anything of value
        if (empty($combinedPart)) {

            throw new ParserException('Error parsing what seams to be a |-combined assertion ' . $docString);
        }

        // Now we have to create all the separate assertions for each part of the $combinedPart string
        $assertionList = new AssertionList();
        foreach (explode('|', $combinedPart) as $partString) {

            // Rebuild the assertion string with one partial string of the combined part
            $tmp = $assertionArray;
            $tmp[$combinedIndex] = $partString;
            $assertion = $this->parseAssertion(implode(' ', $tmp));

            if (is_bool($assertion)) {

                continue;

            } else {

                $assertionList->add($assertion);
            }
            $assertion = false;
        }

        // We got everything. Create a ChainedAssertion instance
        return new ChainedAssertion($assertionList, '||');
    }

    /**
     * @param $docString
     *
     * @return bool
     */
    private function filterVariable($docString)
    {
        // Explode the string to get the different pieces
        $explodedString = explode(' ', $docString);

        // Filter for the first variable. The first as there might be a variable name in any following description
        foreach ($explodedString as $stringPiece) {

            // Check if we got a variable
            $stringPiece = trim($stringPiece);
            if (strpos(
                    $stringPiece,
                    '$'
                ) === 0 || $stringPiece === PBC_KEYWORD_RESULT || $stringPiece === PBC_KEYWORD_OLD
            ) {

                return $stringPiece;
            }
        }

        // We found nothing; tell them.
        return false;
    }

    /**
     * @param $docString
     *
     * @return bool
     */
    private function filterOrCombinator($docString)
    {
        if (strpos($docString, '|')) {

            return true;
        }

        // We found nothing; tell them.
        return false;
    }

    /**
     * @param $docString
     *
     * @return bool
     */
    private function filterTypedCollection($docString)
    {
        $tmp = strpos($docString, 'array<');
        if ($tmp !== false && strpos($docString, '>') > $tmp) {

            $stringPiece = explode('array<', $docString)[1];
            return strstr($stringPiece, '>', true);
        }

        // We found nothing; tell them.
        return false;
    }

    /**
     * @param $docString
     * @return bool|string
     * @throws \InvalidArgumentException
     */
    private function filterType($docString)
    {
        // Explode the string to get the different pieces
        $explodedString = explode(' ', $docString);

        // Filter for the first variable. The first as there might be a variable name in any following description
        $validTypes = array_flip($this->validSimpleTypes);
        foreach ($explodedString as $stringPiece) {

            // Check if we got a variable
            $stringPiece = strtolower(trim($stringPiece));
            if (isset($validTypes[$stringPiece])) {

                return $stringPiece;

            } elseif (isset($this->simpleTypeMappings[$stringPiece])) {

                return $this->simpleTypeMappings[$stringPiece];

            } elseif ($stringPiece === 'mixed') {

                throw new \InvalidArgumentException;
            }
        }

        // We found nothing; tell them.
        return false;
    }

    /**
     * @param $docString
     *
     * @return bool
     */
    private function filterClass($docString)
    {
        // Explode the string to get the different pieces
        $explodedString = explode(' ', $docString);

        // Check if we got a valid docsting, if so the first part must begin with @
        if (strpos($explodedString[0], '@') !== 0) {

            return false;
        }

        // We assume we got a class if the second part is no scalar type and no variable
        $validTypes = array_flip($this->validSimpleTypes);
        $stringPiece = trim($explodedString[1]);
        if (strpos($stringPiece, '$') === false && !isset($validTypes[strtolower($stringPiece)])) {

            // If we got "void" we do not need to bother
            if ($stringPiece !== 'void') {

                return $stringPiece;
            }
        }

        // We found nothing; tell them.
        return false;
    }
}