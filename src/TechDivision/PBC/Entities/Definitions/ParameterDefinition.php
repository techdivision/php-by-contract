<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 16.07.13
 * Time: 14:05
 * To change this template use File | Settings | File Templates.
 */
namespace TechDivision\PBC\Entities\Definitions;

/**
 * Class ParameterDefinition
 */
class ParameterDefinition
{
    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $defaultValue;

    /**
     * Default constructor
     */
    public function __construct()
    {
        $this->type = '';
        $this->name = '';
        $this->defaultValue = '';
    }

    /**
     * @param string $mode
     * @return string
     */
    public function getString($mode = 'definition')
    {
        // Prepare the parts
        $stringParts = array();

        if ($mode === 'call') {

            // Get the name
            $stringParts[] = $this->name;

        } elseif ($mode === 'definition') {

            // Get the type
            $stringParts[] = $this->type;

            // Get the name
            $stringParts[] = $this->name;

            if ($this->defaultValue !== '') {

                // Get the default value
                $stringParts[] = '=';
                $stringParts[] = $this->defaultValue;
            }

        } else {

            return '';
        }

        return implode(' ', $stringParts);
    }
}