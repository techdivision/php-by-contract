<?php
/**
 * TechDivision\PBC\Parser\AbstractStructureParser
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 */
namespace TechDivision\PBC\Parser;

use TechDivision\PBC\Interfaces\StructureParserInterface;
use TechDivision\PBC\Exceptions\ParserException;
use TechDivision\PBC\Entities\Definitions\StructureDefinitionHierarchy;
use TechDivision\PBC\StructureMap;

/**
 * @package     TechDivision\PBC
 * @subpackage  Parser
 * @copyright   Copyright (c) 2013 <info@techdivision.com> - TechDivision GmbH
 * @license     http://opensource.org/licenses/osl-3.0.php
 *              Open Software License (OSL 3.0)
 * @author      Bernhard Wick <b.wick@techdivision.com>
 */
abstract class AbstractStructureParser extends AbstractParser implements StructureParserInterface
{
    /**
     * @var StructureMap
     */
    protected $structureMap;

    /**
     * @var StructureDefinitionHierarchy
     */
    protected $structureDefinitionHierarchy;

    protected $file;

    protected $tokens = array();

    protected $tokenCount;

    /**
     * @param                              $file
     * @param StructureMap                 $structureMap
     * @param StructureDefinitionHierarchy $structureDefinitionHierarchy
     *
     * @throws ParserException
     */
    public function __construct(
        $file,
        StructureMap $structureMap,
        StructureDefinitionHierarchy & $structureDefinitionHierarchy
    ) {
        // Check if we can use the file
        if (!is_readable($file)) {

            throw new ParserException('Could not read input file ' . $file);
        }

        // We need the file saved
        $this->file = $file;

        // Get all the tokens and count them
        $this->tokens = token_get_all(file_get_contents($file));
        $this->tokenCount = count($this->tokens);

        $this->structureMap = $structureMap;
        $this->structureDefinitionHierarchy = $structureDefinitionHierarchy;
    }

    /**
     * Will check a token array for the occurrence of a certain on (class, interface or trait)
     *
     * @return bool|string
     */
    protected function getStructureToken()
    {
        for ($i = 0; $i < $this->tokenCount; $i++) {

            switch ($this->tokens[$i][0]) {

                case T_CLASS:

                    return 'class';
                    break;

                case T_INTERFACE:

                    return 'interface';
                    break;

                case T_TRAIT:

                    return 'trait';
                    break;

                default:

                    continue;
                    break;
            }
        }

        // We are still here? That should not be.
        return false;
    }

    /**
     * Will check if a certain structure was mentioned in one(!) use statement.
     * If we will return true we might also remove the use statement from our collection.
     *
     * @param array  $usedNamespaces
     * @param string $namespace
     * @param string $structureName
     * @param bool   $remove
     *
     * @return bool|string
     */
    protected function resolveUsedNamespace(& $usedNamespaces, $namespace, $structureName, $remove = true)
    {
        // If there was no useful name passed we can fail right here
        if (empty($structureName)) {

            return false;
        }

        // Walk over all namespaces and if we find something we will act accordingly.
        $result = $structureName;
        foreach ($usedNamespaces as $key => $usedNamespace) {

            // Check if the last part of the use statement is our structure
            $tmp = explode('\\', $usedNamespace);
            if (array_pop($tmp) === $structureName) {

                // Should we remove it from the array? Did we succeed before too? If so we have to fail to not remove
                // the wrong statement.
                if ($remove === true) {

                    unset($usedNamespaces[$key]);
                }

                // Tell them we succeeded
                return trim(implode('\\', $tmp) . '\\' . $structureName, '\\');
            }
        }

        // We did not seem to have found anything. Might it be that we are in our own namespace?
        if ($namespace !== null && strpos($structureName, '\\') !== 0) {

            return $namespace . '\\' . $structureName;
        }

        // Still here? Return what we got.
        return $result;
    }

    /**
     * @return array
     */
    protected function getConstants()
    {
        // Check the tokens
        $constants = array();
        for ($i = 0; $i < $this->tokenCount; $i++) {

            // If we got the class name
            if ($this->tokens[$i][0] === T_CONST) {

                for ($j = $i + 1; $j < $this->tokenCount; $j++) {

                    if ($this->tokens[$j] === ';') {

                        break;

                    } elseif ($this->tokens[$j][0] === T_STRING) {

                        $constants[$this->tokens[$j][1]] = '';

                        for ($k = $j + 1; $k < count($this->tokens); $k++) {

                            if ($this->tokens[$k] === ';') {

                                break;

                            } elseif (is_array($this->tokens[$k]) && $this->tokens[$k][0] !== '=') {

                                $constants[$this->tokens[$j][1]] .= $this->tokens[$k][1];
                            }
                        }

                        // Now trim what we got
                        $constants[$this->tokens[$j][1]] = trim($constants[$this->tokens[$j][1]]);
                    }
                }
            }
        }

        // Return what we did or did not found
        return $constants;
    }

    /**
     * @param $structureToken
     *
     * @return array|bool
     */
    protected function getStructureTokens($structureToken)
    {
        // Now iterate over the array and filter different classes from it
        $result = array();
        for ($i = 0; $i < $this->tokenCount; $i++) {

            // If we got a class keyword, we have to check how far the class extends,
            // then copy the array withing that bounds
            if (is_array($this->tokens[$i]) && $this->tokens[$i][0] === $structureToken) {

                // The lower bound should be the last semicolon|closing curly bracket|PHP tag before the class
                $lowerBound = 0;
                for ($j = $i - 1; $j >= 0; $j--) {

                    if ($this->tokens[$j] === ';' || $this->tokens[$j] === '}' ||
                        is_array($this->tokens[$j]) && $this->tokens[$j][0] === T_OPEN_TAG
                    ) {

                        $lowerBound = $j;
                        break;
                    }
                }

                // The upper bound should be the first time the curly brackets are even again
                $upperBound = $this->tokenCount - 1;
                $bracketCounter = null;
                for ($j = $i + 1; $j < count($this->tokens); $j++) {

                    if ($this->tokens[$j] === '{' || $this->tokens[$j][0] === T_CURLY_OPEN) {

                        // If we still got null set to 0
                        if ($bracketCounter === null) {

                            $bracketCounter = 0;
                        }

                        $bracketCounter++;

                    } elseif ($this->tokens[$j] === '}') {

                        // If we still got null set to 0
                        if ($bracketCounter === null) {

                            $bracketCounter = 0;
                        }

                        $bracketCounter--;
                    }

                    // Do we have an even amount of brackets yet?
                    if ($bracketCounter === 0) {

                        $upperBound = $j;
                        break;
                    }
                }

                $result[] = array_slice($this->tokens, $lowerBound, $upperBound - $lowerBound);
            }
        }

        // Last line of defence; did we get something?
        if (empty($result)) {

            return false;
        }

        return $result;
    }

    /**
     * @return string
     */
    protected function getNamespace()
    {
        // Check the tokens
        $namespace = '';
        for ($i = 0; $i < $this->tokenCount; $i++) {

            // If we got the namespace
            if ($this->tokens[$i][0] === T_NAMESPACE) {

                for ($j = $i + 1; $j < count($this->tokens); $j++) {

                    if ($this->tokens[$j][0] === T_STRING) {

                        $namespace .= '\\' . $this->tokens[$j][1];

                    } elseif ($this->tokens[$j] === '{' ||
                        $this->tokens[$j] === ';' ||
                        $this->tokens[$j][0] === T_CURLY_OPEN
                    ) {

                        break;
                    }
                }
            }
        }

        // Return what we did or did not found
        return substr($namespace, 1);
    }

    /**
     * @return array
     */
    protected function getUsedNamespaces()
    {
        // Check the tokens
        $namespaces = array();
        for ($i = 0; $i < $this->tokenCount; $i++) {

            // If we got a use statement
            if ($this->tokens[$i][0] === T_USE) {

                $namespace = '';
                for ($j = $i + 1; $j < count($this->tokens); $j++) {

                    if ($this->tokens[$j][0] === T_STRING) {

                        $namespace .= '\\' . $this->tokens[$j][1];

                    } elseif ($this->tokens[$j] === '{' ||
                        $this->tokens[$j] === ';' ||
                        $this->tokens[$j][0] === T_CURLY_OPEN
                    ) {

                        $namespaces[] = $namespace;
                        break;
                    }
                }
            }
        }

        // Return what we did or did not found
        return $namespaces;
    }
}
