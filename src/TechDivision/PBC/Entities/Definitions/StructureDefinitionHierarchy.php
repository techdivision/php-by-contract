<?php
/**
 * TechDivision\PBC\Entities\Definitions\StructureDefinitionTree
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 */
namespace TechDivision\PBC\Entities\Definitions;

use TechDivision\PBC\Interfaces\StructureDefinitionInterface;

/**
 * @package     TechDivision\PBC
 * @subpackage  Entities
 * @copyright   Copyright (c) 2013 <info@techdivision.com> - TechDivision GmbH
 * @license     http://opensource.org/licenses/osl-3.0.php
 *              Open Software License (OSL 3.0)
 * @author      Bernhard Wick <b.wick@techdivision.com>
 */
class StructureDefinitionHierarchy
{
    /**
     * @var array
     */
    protected $nodes = array();

    /**
     *
     *
     * @param StructureDefinitionInterface $node
     * @return bool
     */
    public function insert(StructureDefinitionInterface $node)
    {
        // Already here? Nothing to do then
        $qualifiedName = $node->getQualifiedName();
        if (!empty($this->nodes[$qualifiedName])) {

            return true;
        }

        // Add the node
        $this->nodes[$qualifiedName] = $node;

        // Add empty entries for the dependencies so we can check if all where added
        $dependencies = $node->getDependencies();
        foreach ($dependencies as $dependency) {

            if (!empty($this->nodes[$dependency])) {

                continue;

            } else {

                $this->nodes[$dependency] = null;
            }
        }

        // Still here? Sounds great
        return true;
    }

    /**
     * @param $entryName
     * @return bool
     */
    public function getEntry($entryName)
    {
        if (!isset($this->nodes[$entryName]) || !is_null($this->nodes[$entryName])) {

            return false;
        }

        return $this->nodes[$entryName];
    }

    /**
     * @param $entryName
     * @return bool
     */
    public function entryExists($entryName)
    {
        if (!isset($this->nodes[$entryName]) || !is_null($this->nodes[$entryName])) {

            return false;

        } else {

            return true;
        }
    }

    /**
     * Will return true if all possible node entries contain data
     *
     * @return bool
     */
    public function isComplete()
    {
        foreach($this->nodes as $node) {

            if (is_null($node)) {

                return false;
            }
        }

        return true;
    }
}