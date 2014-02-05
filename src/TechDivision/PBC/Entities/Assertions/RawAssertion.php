<?php
/**
 * File containing the RawAssertion class
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

namespace TechDivision\PBC\Entities\Assertions;

/**
 * TechDivision\PBC\Entities\Assertions\RawAssertion
 *
 * This class provides a way of using php syntax assertions
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
class RawAssertion extends AbstractAssertion
{
    /**
     * @var string $content Php code string we want to execute as an assertion
     */
    public $content;

    /**
     * Default constructor
     *
     * @param string $content Php code string we want to execute as an assertion
     */
    public function __construct($content)
    {
        $this->content = $content;

        parent::__construct();
    }

    /**
     * Will return a string representation of this assertion
     *
     * @return string
     */
    public function getString()
    {
        return (string)$this->content;
    }

    /**
     * Invert the logical meaning of this assertion
     *
     * @return bool
     */
    public function invert()
    {
        if ($this->inverted === false) {

            $this->content = '!(' . $this->content . ')';
            $this->inverted = true;

            return true;

        } elseif ($this->inverted === true) {

            // Just unset the parts of $this->content we do not need
            unset($this->content[0]);
            unset($this->content[1]);
            unset($this->content[strlen($this->content) - 1]);

            $this->inverted = false;

            return true;

        } else {

            return false;
        }
    }
}
