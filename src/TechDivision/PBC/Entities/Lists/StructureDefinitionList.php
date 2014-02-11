<?php
/**
 * File containing the StructureDefinitionList class
 *
 * PHP version 5
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Entities
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 *             Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */

namespace TechDivision\PBC\Entities\Lists;

/**
 * TechDivision\PBC\Entities\Lists\StructureDefinitionList
 *
 * A typed list for StructureDefinition objects
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Entities
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 *             Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */
class StructureDefinitionList extends AbstractTypedList
{
    /**
     * Default constructor
     */
    public function __construct()
    {
        $this->itemType = 'TechDivision\PBC\Interfaces\StructureDefinitionInterface';
        $this->defaultOffset = 'name';
    }
}
