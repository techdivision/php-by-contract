<?php
/**
 * File containing the Structure class
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

namespace TechDivision\PBC\Entities\Definitions;

/**
 * TechDivision\PBC\Entities\Definitions\Structure
 *
 * This class is used as a DTO fort our structure map and etc.
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
class Structure
{
    /**
     * @var array $allowedTypes Will contain types which are allowed for a structure instance
     */
    protected $allowedTypes;

    /**
     * @var int $cTime The manipulation time of the structure file
     */
    protected $cTime;

    /**
     * @var string $identifier The identifier (namespace + structure name) of the structure
     */
    protected $identifier;

    /**
     * @var string $path Path to the file containing the structure definition
     */
    protected $path;

    /*
     * @var string $type Type of the structure e.g. "class"
     */
    protected $type;

    /**
     * @var boolean $hasContracts Does the structure even have contracts
     */
    protected $hasContracts;

    /**
     * @var boolean $enforced Do we have to enforce contracts (if any) within this structure?
     */
    protected $enforced;

    /**
     * Default constructor
     *
     * @param int     $cTime        The manipulation time of the structure file
     * @param string  $identifier   The identifier (namespace + structure name) of the structure
     * @param string  $path         Path to the file containing the structure definition
     * @param string  $type         Type of the structure e.g. "class"
     * @param boolean $hasContracts Does the structure even have contracts
     * @param boolean $enforced     Do we have to enforce contracts (if any) within this structure?
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($cTime, $identifier, $path, $type, $hasContracts = false, $enforced = false)
    {
        // Set the attributes.
        $this->cTime = $cTime;
        $this->identifier = $identifier;
        $this->path = $path;
        $this->hasContracts = $hasContracts;
        $this->enforced = $enforced;
        $this->allowedTypes = array('class', 'interface', 'trait');

        // Check if we got an allowed value for the type.
        $allowedTypes = array_flip($this->allowedTypes);
        if (!isset($allowedTypes[$type])) {

            throw new \InvalidArgumentException();
        }

        $this->type = $type;
    }

    /**
     * Getter for manipulation time
     *
     * @return int
     */
    public function getCTime()
    {
        return $this->cTime;
    }

    /**
     * Getter for identifier
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Getter for the path of the structure
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Getter for the structure type
     *
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Does the structure have any contracts
     *
     * @return bool
     */
    public function hasContracts()
    {
        return (bool)$this->hasContracts;
    }

    /**
     * Is this structure enforced?
     *
     * @return bool
     */
    public function isEnforced()
    {
        return (bool)$this->enforced;
    }
}
