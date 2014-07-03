<?php
/**
 * File containing the FileDefinition class
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

namespace TechDivision\PBC\Entities\Definitions;

use TechDivision\PBC\Entities\Lists\StructureDefinitionList;

/**
 * TechDivision\PBC\Entities\Definitions\FileDefinition
 *
 * Provides a definition of a file (containing a PHP structure)
 *
 * @category   Php-by-contract
 * @package    TechDivision\PBC
 * @subpackage Entities
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */
class FileDefinition
{
    /**
     * @var string $path Path to the file
     */
    public $path;

    /**
     * @var string $name Name of the file including extension
     */
    public $name;

    /**
     * @var string $namespace Namespace of the enclosed structure (if any)
     */
    public $namespace;

    /**
     * @var array $usedNamespaces Qualified structure names referenced by use statements within the file
     */
    public $usedNamespaces;

    /**
     * @var \TechDivision\PBC\Entities\Lists\StructureDefinitionList $structureDefinitions
     *          List of structures in the file
     */
    public $structureDefinitions;

    /**
     * Default constructor
     */
    public function __construct()
    {
        $this->path = '';
        $this->name = '';
        $this->namespace = '';
        $this->usedNamespaces = array();
        $this->structureDefinitions = new StructureDefinitionList();
    }
}
