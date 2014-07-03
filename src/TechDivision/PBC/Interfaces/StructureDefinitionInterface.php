<?php
/**
 * File containing the StructureDefinitionInterface interface
 *
 * PHP version 5
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Entities
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */

namespace TechDivision\PBC\Interfaces;

use TechDivision\PBC\Entities\Lists\TypedListList;

/**
 * TechDivision\PBC\Interfaces\StructureDefinitionInterface
 *
 * Public interface for structure definitions
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Interfaces
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */
interface StructureDefinitionInterface
{
    /**
     * Will return the qualified name of a structure
     *
     * @return string
     */
    public function getQualifiedName();

    /**
     * Will return the type of the definition.
     *
     * @return string
     */
    public function getType();

    /**
     * Will return a list of all dependencies of a structure like parent class, implemented interfaces, etc.
     *
     * @return array
     */
    public function getDependencies();

    /**
     * Will return true if the structure has (a) parent structure(s).
     * Will return false if not.
     *
     * @return bool
     */
    public function hasParents();

    /**
     * Will return all invariants of a structure.
     *
     * @return TypedListList
     */
    public function getInvariants();
}
