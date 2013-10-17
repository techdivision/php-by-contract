<?php

namespace TechDivision\PBC\Entities\Definitions;

/**
 * Class Structure
 *
 * @package TechDivision\PBC\Entities\Definitions
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
     * Default constructor.
     *
     * @param $cTime
     * @param $identifier
     * @param $path
     * @param $type
     *
     * @throws  \InvalidArgumentException
     */
    public function __construct($cTime, $identifier, $path, $type)
    {
        // Set the attributes.
        $this->cTime = $cTime;
        $this->identifier = $identifier;
        $this->path = $path;

        // Check if we got an allowed value for the type.
        $allowedTypes = array_flip($this->allowedTypes);
        if (!isset($allowedTypes[$type])) {

            throw new \InvalidArgumentException();
        }

        $this->type = $type;
    }


    /**
     * @param int $cTime
     */
    public function setCTime($cTime)
    {
        $this->cTime = $cTime;
    }

    /**
     * @return int
     */
    public function getCTime()
    {
        return $this->cTime;
    }

    /**
     * @param string $identifier
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param $type
     * @throws \InvalidArgumentException
     */
    public function setType($type)
    {
        $allowedTypes = array_flip($this->allowedTypes);
        if (!isset($allowedTypes[$type])) {

            throw new \InvalidArgumentException();
        }

        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }


}