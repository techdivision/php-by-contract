<?php
/**
 * TechDivision\PBC\Entities\Definitions\Structure
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 */
namespace TechDivision\PBC\Entities\Definitions;

/**
 * @package     TechDivision\PBC
 * @subpackage  Entities
 * @copyright   Copyright (c) 2013 <info@techdivision.com> - TechDivision GmbH
 * @license     http://opensource.org/licenses/osl-3.0.php
 *              Open Software License (OSL 3.0)
 * @author      Bernhard Wick <b.wick@techdivision.com>
 */
class Structure
{
    /**
     * @var array
     */
    private $allowedTypes = array('class', 'interface', 'trait');

    /**
     * @var int
     */
    private $cTime;

    /**
     * @var string
     */
    private $identifier;

    /**
     * @var string
     */
    private $path;

    /*
     * @var string
     */
    private $type;

    /**
     * @var boolean
     */
    private $hasContracts;

    /**
     * @param $cTime
     * @param $identifier
     * @param $path
     * @param $type
     * @param $hasContracts
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($cTime, $identifier, $path, $type, $hasContracts = true)
    {
        // Set the attributes.
        $this->cTime = $cTime;
        $this->identifier = $identifier;
        $this->path = $path;
        $this->hasContracts = $hasContracts;

        // Check if we got an allowed value for the type.
        $allowedTypes = array_flip($this->allowedTypes);
        if (!isset($allowedTypes[$type])) {

            throw new \InvalidArgumentException();
        }

        $this->type = $type;
    }

    /**
     * @return int
     */
    public function getCTime()
    {
        return $this->cTime;
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return bool
     */
    public function hasContracts()
    {
        return (bool)$this->hasContracts;
    }
}
