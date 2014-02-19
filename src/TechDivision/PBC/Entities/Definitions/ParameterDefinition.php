<?php
/**
 * File containing the ParameterDefinition class
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
 * TechDivision\PBC\Entities\Definitions\ParameterDefinition
 *
 * Allows us to keep track of a functions parameters
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
class ParameterDefinition
{
    /**
     * @var string $type Type hint (if any)
     */
    public $type;

    /**
     * @var string $name Name of the parameter
     */
    public $name;

    /**
     * @var mixed $defaultValue The parameter's default value (if any)
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
     * Will return a string representation of the defined parameter
     *
     * @param string $mode We can switch how the string should be structured.
     *                     Choose from "definition", "call"
     *
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
